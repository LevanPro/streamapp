import { Head, useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';
import Aurora from '@/Components/reactbits/Aurora';
import GradientText from '@/Components/reactbits/GradientText';
import ShinyText from '@/Components/reactbits/ShinyText';
import AnimatedContent from '@/Components/reactbits/AnimatedContent';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event) => {
        event.preventDefault();
        post(route('login.store'), { onFinish: () => reset('password') });
    };

    const field =
        'w-full rounded-soft border border-line bg-elevated/60 px-3 py-2.5 text-ink outline-none transition placeholder:text-muted/70 focus:border-accent focus:ring-2 focus:ring-accent/25';

    return (
        <>
            <Head title="Login" />

            <div className="fixed inset-0 -z-10 opacity-70">
                <Aurora
                    colorStops={['#065f46', '#10b981', '#0ea5e9']}
                    amplitude={1.1}
                    blend={0.6}
                    speed={0.5}
                />
            </div>

            <div className="grid min-h-screen place-items-center px-4 py-10">
                <AnimatedContent distance={60} duration={0.9}>
                    <section className="grid w-[min(980px,96vw)] overflow-hidden rounded-panel border border-line bg-panel/70 shadow-panel backdrop-blur-xl md:grid-cols-[1.05fr_0.95fr]">
                        <div className="relative hidden flex-col justify-center gap-3 p-10 md:flex">
                            <p className="text-xs font-bold uppercase tracking-[0.18em] text-accent">
                                Private Access
                            </p>
                            <h1 className="text-4xl font-extrabold leading-[1.12]">
                                <GradientText>Your course vault,</GradientText>
                                <br />
                                <span className="text-ink">in the dark.</span>
                            </h1>
                            <p className="max-w-[42ch] leading-relaxed text-muted">
                                Stream lessons, open resources, and pick up
                                exactly where you left off.
                            </p>
                        </div>

                        <div className="border-line p-8 md:border-l md:bg-panel/40">
                            <h2 className="text-xl font-bold text-ink">
                                <ShinyText text="Sign In" speed={4} />
                            </h2>
                            <p className="mb-6 mt-1 text-sm text-muted">
                                Private access only.
                            </p>

                            <form
                                onSubmit={submit}
                                className="grid gap-3.5"
                                noValidate
                            >
                                <label className="grid gap-1.5">
                                    <span className="text-sm font-medium text-ink">
                                        Email
                                    </span>
                                    <input
                                        type="email"
                                        value={data.email}
                                        onChange={(e) =>
                                            setData('email', e.target.value)
                                        }
                                        required
                                        autoFocus
                                        autoComplete="email"
                                        placeholder="you@example.com"
                                        className={field}
                                    />
                                    {errors.email && (
                                        <span className="text-sm text-danger">
                                            {errors.email}
                                        </span>
                                    )}
                                </label>

                                <label className="grid gap-1.5">
                                    <span className="text-sm font-medium text-ink">
                                        Password
                                    </span>
                                    <input
                                        type="password"
                                        value={data.password}
                                        onChange={(e) =>
                                            setData('password', e.target.value)
                                        }
                                        required
                                        autoComplete="current-password"
                                        placeholder="••••••••"
                                        className={field}
                                    />
                                    {errors.password && (
                                        <span className="text-sm text-danger">
                                            {errors.password}
                                        </span>
                                    )}
                                </label>

                                <label className="flex items-center gap-2 text-sm text-muted">
                                    <input
                                        type="checkbox"
                                        checked={data.remember}
                                        onChange={(e) =>
                                            setData(
                                                'remember',
                                                e.target.checked,
                                            )
                                        }
                                        className="accent-[var(--c-accent)]"
                                    />
                                    <span>Remember me</span>
                                </label>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="mt-2 inline-flex items-center justify-center rounded-soft bg-accent px-4 py-2.5 text-sm font-semibold text-accent-ink shadow-accent transition hover:bg-accent-dark disabled:opacity-60"
                                >
                                    {processing ? 'Signing in…' : 'Log in'}
                                </button>
                            </form>
                        </div>
                    </section>
                </AnimatedContent>
            </div>
        </>
    );
}
