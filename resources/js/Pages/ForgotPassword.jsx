import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AuthCard from '@/components/AuthCard';
import { Button } from '@/components/ui/button';
import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

export default function ForgotPassword({ expireMinutes }) {
    const form = useForm({ email: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post('/forgot-password', { preserveScroll: true, onSuccess: () => form.reset('email') });
    };

    return (
        <AuthCard title="Lupa password" description="Kami kirim tautan untuk mengganti password ke emailmu">
            <form onSubmit={submit} className="space-y-4">
                <Field>
                    <FieldLabel htmlFor="email">Email akun</FieldLabel>
                    <Input
                        id="email"
                        type="email"
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                        aria-invalid={!!form.errors.email}
                        autoComplete="email"
                        autoFocus
                    />
                    <FieldDescription>Tautan berlaku {expireMinutes} menit dan hanya bisa dipakai sekali.</FieldDescription>
                    <FieldError>{form.errors.email}</FieldError>
                </Field>
                <Button className="w-full" size="lg" disabled={form.processing}>
                    {form.processing ? 'Mengirim…' : 'Kirim tautan'}
                </Button>
                <Button asChild variant="ghost" className="w-full">
                    <Link href="/login">
                        <ArrowLeft />
                        Kembali ke halaman masuk
                    </Link>
                </Button>
            </form>
        </AuthCard>
    );
}
