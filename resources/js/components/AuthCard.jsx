import { Head, usePage } from '@inertiajs/react';
import { CircleCheck, Store } from 'lucide-react';
import { Alert, AlertTitle } from '@/components/ui/alert';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

// Shared frame of the pages shown before logging in (login, forgot and change
// password). A flash message from the previous step shows on top.
export default function AuthCard({ title, description, children }) {
    const { flash } = usePage().props;

    return (
        <div className="flex min-h-dvh items-center justify-center p-4">
            <Head title={title} />
            <Card className="w-full max-w-sm">
                <CardHeader className="justify-items-center text-center">
                    <span className="mb-2 flex size-12 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <Store className="size-6" />
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
    );
}
