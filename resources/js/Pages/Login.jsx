import { Link, useForm } from '@inertiajs/react';
import AuthCard from '@/components/AuthCard';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

export default function Login() {
    const form = useForm({ email: '', password: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post('/login', { onFinish: () => form.reset('password') });
    };

    return (
        <AuthCard title="Warung" description="Masuk untuk mulai berjualan">
            <form onSubmit={submit} className="space-y-4">
                <Field>
                    <FieldLabel htmlFor="email">Email</FieldLabel>
                    <Input
                        id="email"
                        type="email"
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                        aria-invalid={!!form.errors.email}
                        autoComplete="username"
                        autoFocus
                    />
                    <FieldError>{form.errors.email}</FieldError>
                </Field>
                <Field>
                    <div className="flex items-center justify-between">
                        <FieldLabel htmlFor="password">Password</FieldLabel>
                        <Button asChild variant="link" size="sm" className="h-auto p-0">
                            <Link href="/forgot-password">Lupa password?</Link>
                        </Button>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        value={form.data.password}
                        onChange={(e) => form.setData('password', e.target.value)}
                        autoComplete="current-password"
                    />
                </Field>
                <Button className="w-full" size="lg" disabled={form.processing}>
                    Masuk
                </Button>
            </form>
        </AuthCard>
    );
}
