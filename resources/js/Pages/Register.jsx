import { Link, router, useForm, usePage } from '@inertiajs/react';
import { MailCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import AuthCard from '@/components/AuthCard';
import PasswordInput from '@/components/PasswordInput';
import Steps from '@/components/Steps';
import { Button } from '@/components/ui/button';
import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

// Step 1a: the address to verify.
function EmailStep() {
    const form = useForm({ email: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post('/register/code', { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <Field>
                <FieldLabel htmlFor="email">Email</FieldLabel>
                <Input
                    id="email"
                    type="email"
                    value={form.data.email}
                    onChange={(e) => form.setData('email', e.target.value)}
                    aria-invalid={!!form.errors.email}
                    autoComplete="email"
                    placeholder="nama@email.com"
                    autoFocus
                />
                <FieldDescription>Kami kirim 6 angka kode verifikasi ke email ini.</FieldDescription>
                <FieldError>{form.errors.email}</FieldError>
            </Field>
            <Button className="w-full" size="lg" disabled={form.processing}>
                {form.processing ? 'Mengirim…' : 'Kirim kode'}
            </Button>
        </form>
    );
}

// Seconds left before another code may be requested, counting down live.
function useCountdown(seconds) {
    const [left, setLeft] = useState(seconds);

    useEffect(() => setLeft(seconds), [seconds]);
    useEffect(() => {
        if (left <= 0) return;
        const timer = setTimeout(() => setLeft((value) => value - 1), 1000);
        return () => clearTimeout(timer);
    }, [left]);

    return left;
}

// Step 1b: the code from the email.
function CodeStep({ email, resendIn, codeMinutes }) {
    const form = useForm({ code: '' });
    const { errors } = usePage().props;
    const wait = useCountdown(resendIn);
    const [resending, setResending] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        form.post('/register/verify', { preserveScroll: true });
    };

    const resend = () =>
        router.post(
            '/register/code',
            { email },
            {
                preserveScroll: true,
                onStart: () => setResending(true),
                onFinish: () => {
                    setResending(false);
                    form.reset('code');
                },
            },
        );

    return (
        <form onSubmit={submit} className="space-y-4">
            <div className="flex items-center gap-3 rounded-md border p-3 text-sm">
                <MailCheck className="size-5 shrink-0 text-primary" />
                <span className="min-w-0 flex-1">
                    Kode dikirim ke <b className="break-all">{email}</b>
                </span>
                <Button type="button" variant="link" size="sm" className="h-auto p-0" onClick={() => router.post('/register/restart')}>
                    Ganti
                </Button>
            </div>
            <Field>
                <FieldLabel htmlFor="code">Kode verifikasi</FieldLabel>
                <Input
                    id="code"
                    inputMode="numeric"
                    autoComplete="one-time-code"
                    maxLength={6}
                    placeholder="••••••"
                    value={form.data.code}
                    onChange={(e) => form.setData('code', e.target.value.replace(/\D/g, ''))}
                    aria-invalid={!!form.errors.code}
                    className="h-14 text-center text-2xl font-bold tracking-[0.5em] md:text-2xl"
                    autoFocus
                />
                <FieldDescription>Berlaku {codeMinutes} menit. Tidak masuk? Cek folder spam.</FieldDescription>
                <FieldError>{form.errors.code || errors.email}</FieldError>
            </Field>
            <Button className="w-full" size="lg" disabled={form.processing || form.data.code.length !== 6}>
                {form.processing ? 'Memeriksa…' : 'Verifikasi'}
            </Button>
            <Button type="button" variant="outline" className="w-full" disabled={wait > 0 || resending} onClick={resend}>
                {wait > 0 ? `Kirim ulang kode (${wait} dtk)` : resending ? 'Mengirim…' : 'Kirim ulang kode'}
            </Button>
        </form>
    );
}

const fields = [
    { name: 'name', label: 'Nama pemilik', autoComplete: 'name', placeholder: 'Nama lengkap' },
    { name: 'phone', label: 'No. telepon / WhatsApp', autoComplete: 'tel', inputMode: 'tel', placeholder: '081234567890' },
    { name: 'store_name', label: 'Nama toko', autoComplete: 'organization', placeholder: 'Warung Bu Siti' },
    { name: 'store_address', label: 'Alamat toko', autoComplete: 'street-address', placeholder: 'Jalan, nomor, kota' },
];

// Step 2: who and which store. The email comes from the session, not from here.
function DetailsStep({ email }) {
    const form = useForm({
        name: '',
        phone: '',
        store_name: '',
        store_address: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post('/register', { preserveScroll: true, onError: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <div className="flex items-center gap-3 rounded-md border bg-muted/50 p-3 text-sm">
                <MailCheck className="size-5 shrink-0 text-primary" />
                <span className="min-w-0 flex-1 break-all">{email}</span>
                <Button type="button" variant="link" size="sm" className="h-auto p-0" onClick={() => router.post('/register/restart')}>
                    Ganti
                </Button>
            </div>
            <FieldError>{form.errors.email}</FieldError>

            {fields.map(({ name, label, ...input }, index) => (
                <Field key={name}>
                    <FieldLabel htmlFor={name}>{label}</FieldLabel>
                    <Input
                        id={name}
                        value={form.data[name]}
                        onChange={(e) => form.setData(name, e.target.value)}
                        aria-invalid={!!form.errors[name]}
                        autoFocus={index === 0}
                        {...input}
                    />
                    <FieldError>{form.errors[name]}</FieldError>
                </Field>
            ))}

            <Field>
                <FieldLabel htmlFor="password">Password</FieldLabel>
                <PasswordInput
                    id="password"
                    value={form.data.password}
                    onChange={(e) => form.setData('password', e.target.value)}
                    aria-invalid={!!form.errors.password}
                    autoComplete="new-password"
                />
                <FieldDescription>Minimal 8 karakter.</FieldDescription>
                <FieldError>{form.errors.password}</FieldError>
            </Field>
            <Field>
                <FieldLabel htmlFor="password_confirmation">Ulangi password</FieldLabel>
                <PasswordInput
                    id="password_confirmation"
                    value={form.data.password_confirmation}
                    onChange={(e) => form.setData('password_confirmation', e.target.value)}
                    autoComplete="new-password"
                />
            </Field>
            <Button className="w-full" size="lg" disabled={form.processing}>
                {form.processing ? 'Membuat akun…' : 'Buat akun & pilih paket'}
            </Button>
        </form>
    );
}

export default function Register({ email, verified, resendIn, codeMinutes }) {
    return (
        <AuthCard title="Daftar" description="Buat akun untuk usahamu" className="max-w-md">
            <Steps current={verified ? 1 : 0} />
            {!email && <EmailStep />}
            {email && !verified && <CodeStep email={email} resendIn={resendIn} codeMinutes={codeMinutes} />}
            {verified && <DetailsStep email={email} />}
            <p className="text-center text-sm text-muted-foreground">
                Sudah punya akun?{' '}
                <Link href="/login" className="font-medium text-primary underline-offset-4 hover:underline">
                    Masuk
                </Link>
            </p>
        </AuthCard>
    );
}
