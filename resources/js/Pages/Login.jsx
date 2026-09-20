import { Head, useForm } from '@inertiajs/react';

export default function Login() {
    const form = useForm({ email: '', password: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post('/login', { onFinish: () => form.reset('password') });
    };

    return (
        <div className="flex min-h-dvh items-center justify-center p-4">
            <Head title="Masuk" />
            <form onSubmit={submit} className="card w-full max-w-sm space-y-4 p-6">
                <h1 className="text-center text-2xl font-bold">🏪 Warung</h1>
                <div>
                    <label className="mb-1 block text-sm font-medium">Email</label>
                    <input
                        type="email"
                        className="input"
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                        autoComplete="username"
                        autoFocus
                    />
                    {form.errors.email && <p className="mt-1 text-sm text-red-600">{form.errors.email}</p>}
                </div>
                <div>
                    <label className="mb-1 block text-sm font-medium">Password</label>
                    <input
                        type="password"
                        className="input"
                        value={form.data.password}
                        onChange={(e) => form.setData('password', e.target.value)}
                        autoComplete="current-password"
                    />
                </div>
                <button className="btn-primary w-full" disabled={form.processing}>
                    Masuk
                </button>
            </form>
        </div>
    );
}
