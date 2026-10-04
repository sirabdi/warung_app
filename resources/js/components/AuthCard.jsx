import { Head, usePage } from '@inertiajs/react';
import { CircleCheck, Store } from 'lucide-react';
import Footer from '@/components/Footer';
import { Alert, AlertTitle } from '@/components/ui/alert';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

// Shared frame of the small standalone pages (login, register, password,
// subscription expired, payment). A flash message from the previous step
// shows on top.
export default function AuthCard({ title, description, icon: Icon = Store, className, children }) {
    const { flash } = usePage().props;

    return (
        <div className="flex min-h-dvh flex-col">
            <Head title={title} />
            <div className="flex flex-1 items-center justify-center p-4">
                <Card className={cn('w-full max-w-sm', className)}>
                    <CardHeader className="justify-items-center text-center">
                        <span className="mb-2 flex size-12 items-center justify-center rounded-md bg-primary text-primary-foreground">
                            <Icon className="size-6" />
                        </span>
                        <CardTitle className="text-2xl">{title}</CardTitle>
                        <CardDescription>{description}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {flash?.success && (
                            <Alert>
                                <CircleCheck className="text-primary" />
                                <AlertTitle className="line-clamp-none">{flash.success}</AlertTitle>
                            </Alert>
                        )}
                        {children}
                    </CardContent>
                </Card>
            </div>
            <Footer tagline />
        </div>
    );
}
