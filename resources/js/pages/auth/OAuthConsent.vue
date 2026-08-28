<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ShieldCheck, X } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { approve, deny } from '@/routes/passport/authorizations';

interface Scope {
    id: string;
    description: string;
}

defineProps<{
    client: { id: string; name: string };
    user: { email: string };
    scopes: Scope[];
    authToken: string;
    csrf: string;
}>();

const processing = ref(false);

function onApproveSubmit(): void {
    // Show loading state...
    processing.value = true;

    // After form submission, watch for redirect and close window...
    setTimeout(function () {
        const checkRedirect = setInterval(function () {
            // If URL changed or we have OAuth params, redirect happened...
            if (
                !window.location.href.includes('/oauth/authorize') ||
                window.location.search.includes('code=') ||
                window.location.search.includes('error=')
            ) {
                clearInterval(checkRedirect);
                window.close();
            }
        }, 100);

        // Fallback: Close after five seconds...
        setTimeout(function () {
            clearInterval(checkRedirect);
            window.close();
        }, 5000);
    }, 200);
}

function onDenySubmit(): void {
    setTimeout(function () {
        window.close();
    }, 200);
}
</script>

<template>
    <Head title="Authorize Application" />

    <div
        class="bg-background text-foreground min-h-screen font-sans antialiased"
    >
        <div class="flex min-h-screen items-center justify-center p-4">
            <Card class="w-full max-w-md">
                <CardHeader class="text-center">
                    <div class="mb-4 flex items-center justify-center">
                        <ShieldCheck class="text-primary h-12 w-12" />
                    </div>

                    <CardTitle class="text-2xl tracking-tight">
                        Authorize {{ client.name }}
                    </CardTitle>

                    <CardDescription>
                        Review the permissions requested by this application.
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-4">
                    <!-- User Info -->
                    <div class="bg-muted/50 rounded-lg border p-4">
                        <p class="text-muted-foreground mb-2 text-sm">
                            Logged in as:
                        </p>
                        <p class="font-medium">{{ user.email }}</p>
                    </div>

                    <!-- Scopes / Permissions -->
                    <div v-if="scopes.length > 0" class="space-y-2">
                        <p class="text-sm font-medium">Permissions:</p>

                        <ul class="space-y-2">
                            <li
                                v-for="scope in scopes"
                                :key="scope.id"
                                class="flex items-start gap-2"
                            >
                                <div
                                    class="bg-primary/10 mt-0.5 rounded-full p-1"
                                >
                                    <div
                                        class="bg-primary h-1.5 w-1.5 rounded-full"
                                    ></div>
                                </div>
                                <span class="text-muted-foreground text-sm">
                                    {{ scope.description }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </CardContent>

                <CardFooter class="flex flex-col gap-3 sm:flex-row">
                    <!-- Deny Form -->
                    <form
                        v-bind="deny.form()"
                        class="w-full flex-1"
                        @submit="onDenySubmit"
                    >
                        <input type="hidden" name="_token" :value="csrf" />
                        <input type="hidden" name="_method" value="DELETE" />
                        <input type="hidden" name="state" value="" />
                        <input
                            type="hidden"
                            name="client_id"
                            :value="client.id"
                        />
                        <input
                            type="hidden"
                            name="auth_token"
                            :value="authToken"
                        />
                        <Button
                            type="submit"
                            variant="outline"
                            size="lg"
                            class="w-full"
                        >
                            <X />
                            Cancel
                        </Button>
                    </form>

                    <!-- Approve Form -->
                    <form
                        v-bind="approve.form()"
                        class="w-full flex-1"
                        @submit="onApproveSubmit"
                    >
                        <input type="hidden" name="_token" :value="csrf" />
                        <input type="hidden" name="state" value="" />
                        <input
                            type="hidden"
                            name="client_id"
                            :value="client.id"
                        />
                        <input
                            type="hidden"
                            name="auth_token"
                            :value="authToken"
                        />
                        <Button
                            type="submit"
                            size="lg"
                            class="w-full"
                            :disabled="processing"
                        >
                            <Spinner v-if="processing" />
                            {{ processing ? 'Authorizing...' : 'Authorize' }}
                        </Button>
                    </form>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>
