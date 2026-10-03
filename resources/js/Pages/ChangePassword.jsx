import { Link, useForm } from '@inertiajs/react';
import { AlertCircle } from 'lucide-react';
import AuthCard from '@/components/AuthCard';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

// Opened from the link in the email: /change-password?token=…&email=…
export default function ChangePassword({ token, email }) {
    const form = useForm({ token, email, password: '', password_confirmation: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post('/change-password', { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <AuthCard title="Ganti password" description={`Buat password baru untuk ${email}`}>
            {form.errors.token && (
                <Alert variant="destructive">
                    <AlertCircle />
                    <AlertTitle className="line-clamp-none">{form.errors.token}</AlertTitle>
                    <AlertDescription>
                        <Link href="/forgot-password" className="font-medium underline underline-offset-4">
                            Minta tautan baru
                        </Link>
                    </AlertDescription>
                </Alert>
            )}
            <form onSubmit={submit} className="space-y-4">
                <Field>
                    <FieldLabel htmlFor="password">Password baru</FieldLabel>
                    <Input
                        id="password"
                        type="password"
                        value={form.data.password}
                        onChange={(e) => form.setData('password', e.target.value)}
                        aria-invalid={!!form.errors.password}
                        autoComplete="new-password"
                        autoFocus
                    />
                    <FieldError>{form.errors.password}</FieldError>
                </Field>
                <Field>
                    <FieldLabel htmlFor="password_confirmation">Ulangi password baru</FieldLabel>
                    <Input
                        id="password_confirmation"
                        type="password"
                        value={form.data.password_confirmation}
                        onChange={(e) => form.setData('password_confirmation', e.target.value)}
                        autoComplete="new-password"
                    />
                </Field>
                <Button className="w-full" size="lg" disabled={form.processing}>
                    {form.processing ? 'Menyimpan…' : 'Simpan password'}
                </Button>
            </form>
        </AuthCard>
    );
}
