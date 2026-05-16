// ReactBits — Aurora (vendored, MIT). OGL/WebGL aurora ribbons.
import { useEffect, useRef } from 'react';
import { Color, Mesh, Program, Renderer, Triangle } from 'ogl';

const VERT = `#version 300 es
in vec2 position;
void main() {
  gl_Position = vec4(position, 0.0, 1.0);
}`;

const FRAG = `#version 300 es
precision highp float;

uniform float uTime;
uniform float uAmplitude;
uniform vec3 uColorStops[3];
uniform vec2 uResolution;
uniform float uBlend;

out vec4 fragColor;

vec3 permute(vec3 x) { return mod(((x * 34.0) + 1.0) * x, 289.0); }

float snoise(vec2 v) {
  const vec4 C = vec4(0.211324865405187, 0.366025403784439,
                      -0.577350269189626, 0.024390243902439);
  vec2 i  = floor(v + dot(v, C.yy));
  vec2 x0 = v - i + dot(i, C.xx);
  vec2 i1 = (x0.x > x0.y) ? vec2(1.0, 0.0) : vec2(0.0, 1.0);
  vec4 x12 = x0.xyxy + C.xxzz;
  x12.xy -= i1;
  i = mod(i, 289.0);
  vec3 p = permute(permute(i.y + vec3(0.0, i1.y, 1.0)) + i.x + vec3(0.0, i1.x, 1.0));
  vec3 m = max(0.5 - vec3(dot(x0, x0), dot(x12.xy, x12.xy), dot(x12.zw, x12.zw)), 0.0);
  m = m * m;
  m = m * m;
  vec3 x = 2.0 * fract(p * C.www) - 1.0;
  vec3 h = abs(x) - 0.5;
  vec3 ox = floor(x + 0.5);
  vec3 a0 = x - ox;
  m *= 1.79284291400159 - 0.85373472095314 * (a0 * a0 + h * h);
  vec3 g;
  g.x = a0.x * x0.x + h.x * x0.y;
  g.yz = a0.yz * x12.xz + h.yz * x12.yw;
  return 130.0 * dot(m, g);
}

struct ColorStop { vec3 color; float position; };

#define COLOR_RAMP(colors, factor, finalColor) {              \
  int idx = 0;                                                \
  for (int i = 0; i < 2; i++) {                                \
    ColorStop cur = colors[i];                                 \
    bool inb = cur.position <= factor;                         \
    idx = int(mix(float(idx), float(i), float(inb)));          \
  }                                                            \
  ColorStop cs = colors[idx];                                  \
  ColorStop ns = colors[idx + 1];                              \
  float range = ns.position - cs.position;                     \
  float lerpf = (factor - cs.position) / range;                \
  finalColor = mix(cs.color, ns.color, clamp(lerpf, 0.0, 1.0));\
}

void main() {
  vec2 uv = gl_FragCoord.xy / uResolution;

  ColorStop colors[3];
  colors[0] = ColorStop(uColorStops[0], 0.0);
  colors[1] = ColorStop(uColorStops[1], 0.5);
  colors[2] = ColorStop(uColorStops[2], 1.0);

  vec3 rampColor;
  COLOR_RAMP(colors, uv.x, rampColor);

  float height = snoise(vec2(uv.x * 2.0 + uTime * 0.1, uTime * 0.25)) * 0.5 * uAmplitude;
  height = exp(height);
  height = (uv.y * 2.0 - height + 0.2);
  float intensity = 0.6 * height;

  float midPoint = 0.20;
  float alpha = smoothstep(midPoint - uBlend * 0.5, midPoint + uBlend * 0.5, intensity);

  vec3 col = intensity * rampColor;
  fragColor = vec4(col * alpha, alpha);
}`;

export default function Aurora({
    colorStops = ['#10b981', '#2dd4bf', '#34d399'],
    amplitude = 1.0,
    blend = 0.5,
    speed = 0.6,
    className = '',
}) {
    const ref = useRef(null);

    useEffect(() => {
        const ctn = ref.current;
        if (!ctn) return;

        const renderer = new Renderer({
            alpha: true,
            premultipliedAlpha: true,
            antialias: true,
        });
        const gl = renderer.gl;
        gl.clearColor(0, 0, 0, 0);
        gl.enable(gl.BLEND);
        gl.blendFunc(gl.ONE, gl.ONE_MINUS_SRC_ALPHA);
        gl.canvas.style.backgroundColor = 'transparent';

        let program;

        const resize = () => {
            const w = ctn.offsetWidth;
            const h = ctn.offsetHeight;
            renderer.setSize(w, h);
            if (program) program.uniforms.uResolution.value = [w, h];
        };
        window.addEventListener('resize', resize);

        const geometry = new Triangle(gl);
        if (geometry.attributes.uv) delete geometry.attributes.uv;

        const stops = colorStops.map((hex) => {
            const c = new Color(hex);
            return [c.r, c.g, c.b];
        });

        program = new Program(gl, {
            vertex: VERT,
            fragment: FRAG,
            uniforms: {
                uTime: { value: 0 },
                uAmplitude: { value: amplitude },
                uColorStops: { value: stops },
                uResolution: { value: [ctn.offsetWidth, ctn.offsetHeight] },
                uBlend: { value: blend },
            },
        });

        const mesh = new Mesh(gl, { geometry, program });
        ctn.appendChild(gl.canvas);
        resize();

        let raf = 0;
        const reduce = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        const update = (t) => {
            raf = requestAnimationFrame(update);
            program.uniforms.uTime.value = (t * 0.001 * speed) % 1000;
            renderer.render({ scene: mesh });
        };
        if (reduce) {
            program.uniforms.uTime.value = 4.2;
            renderer.render({ scene: mesh });
        } else {
            raf = requestAnimationFrame(update);
        }

        return () => {
            cancelAnimationFrame(raf);
            window.removeEventListener('resize', resize);
            if (gl.canvas.parentNode === ctn) ctn.removeChild(gl.canvas);
            gl.getExtension('WEBGL_lose_context')?.loseContext();
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [amplitude, blend, speed, JSON.stringify(colorStops)]);

    return <div ref={ref} className={`h-full w-full ${className}`} />;
}
