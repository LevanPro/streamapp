import { Head, useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';
import Card from '@/Components/ui/Card';
import Button from '@/Components/ui/Button';
import Reveal from '@/Components/effects/Reveal';
import AuroraBackground from '@/Components/effects/AuroraBackground';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event) => {
        event.preventDefault();
        post(route('login.store'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title="Login" />
            <AuroraBackground />

            <div className="grid min-h-screen place-items-center px-4 py-10">
                <Reveal
                    as="section"
                    className="grid w-[min(960px,96vw)] gap-3.5 md:grid-cols-[minmax(0,1fr)_minmax(340px,0.9fr)]"
                >
                    <Card className="hidden flex-col justify-center p-6 md:flex bg-[radial-gradient(140%_140%_at_100%_0%,rgba(14,163,149,0.16),transparent_58%)]">
                        <p className="text-xs font-bold uppercase tracking-[0.1em] text-[#0c5d80]">
                            Private Access
                        </p>
                        <h1 className="my-2.5 text-3xl font-extrabold leading-tight">
                            Welcome back to your course vault
                        </h1>
                        <p className="m-0 leading-relaxed text-muted">
                            Sign in to continue streaming lessons, download
                            resources, and track your learning progress.
                        </p>
                    </Card>

                    <Card as="article" className="p-6">
                        <h2 className="m-0 text-xl font-bold">Sign In</h2>
                        <p className="mb-5 mt-1 text-sm text-muted">
                            Private access only.
                        </p>

                        <form onSubmit={submit} className="grid gap-3.5" noValidate>
                            <label className="grid gap-1.5">
                                <span className="text-sm font-medium">Email</span>
                                <input
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                    autoFocus
                                    autoComplete="email"
                                    placeholder="you@example.com"
                                    className="w-full rounded-[10px] border border-line bg-white px-3 py-2.5 text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/25"
                                />
                                {errors.email && (
                                    <span className="text-sm text-danger">
                                        {errors.email}
                                    </span>
                                )}
                            </label>

                            <label className="grid gap-1.5">
                                <span className="text-sm font-medium">Password</span>
                                <input
                                    type="password"
                                    name="password"
                                    value={data.password}
                                    onChange={(e) =>
                                        setData('password', e.target.value)
                                    }
                                    required
                                    autoComplete="current-password"
                                    placeholder="••••••••"
                                    className="w-full rounded-[10px] border border-line bg-white px-3 py-2.5 text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/25"
                                />
                                {errors.password && (
                                    <span className="text-sm text-danger">
                                        {errors.password}
                                    </span>
                                )}
                            </label>

                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    checked={data.remember}
                                    onChange={(e) =>
                                        setData('remember', e.target.checked)
                                    }
                                    className="accent-accent-dark"
                                />
                                <span>Remember me</span>
                            </label>

                            <Button
                                type="submit"
                                disabled={processing}
                                className="mt-2"
                            >
                                {processing ? 'Signing in…' : 'Log in'}
                            </Button>
                        </form>
                    </Card>
                </Reveal>
            </div>
        </>
    );
}
