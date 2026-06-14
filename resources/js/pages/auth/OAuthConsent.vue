<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
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
        class="min-h-screen bg-background font-sans text-foreground antialiased"
    >
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="w-full max-w-md">
                <!-- Card Container -->
                <div
                    class="rounded-lg border bg-card text-card-foreground shadow-sm"
                >
                    <!-- Header -->
                    <div class="flex flex-col space-y-1.5 p-6">
                        <div class="mb-4 flex items-center justify-center">
                            <!-- Shield Icon -->
                            <svg
                                class="h-12 w-12 text-primary"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                ></path>
                            </svg>
                        </div>

                        <h3
                            class="text-center text-2xl leading-none font-semibold tracking-tight"
                        >
                            Authorize {{ client.name }}
                        </h3>

                        <p class="text-center text-sm text-muted-foreground">
                            This application will be able to:<br />Use available
                            MCP functionality.
                        </p>
                    </div>

                    <!-- Content -->
                    <div class="space-y-4 p-6 pt-0">
                        <!-- User Info -->
                        <div class="rounded-lg border bg-muted/50 p-4">
                            <p class="mb-2 text-sm text-muted-foreground">
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
                                        class="mt-0.5 rounded-full bg-primary/10 p-1"
                                    >
                                        <div
                                            class="h-1.5 w-1.5 rounded-full bg-primary"
                                        ></div>
                                    </div>
                                    <span class="text-sm text-muted-foreground">
                                        {{ scope.description }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Footer With Buttons -->
                    <div
                        class="flex flex-col items-center gap-3 p-6 pt-0 sm:flex-row"
                    >
                        <!-- Deny Form -->
                        <form
                            v-bind="deny.form()"
                            class="w-full flex-1"
                            @submit="onDenySubmit"
                        >
                            <input type="hidden" name="_token" :value="csrf" />
                            <input
                                type="hidden"
                                name="_method"
                                value="DELETE"
                            />
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
                            <button
                                type="submit"
                                class="inline-flex h-10 w-full items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium whitespace-nowrap ring-offset-background transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                            >
                                <svg
                                    class="mr-2 h-4 w-4"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    ></path>
                                </svg>
                                Cancel
                            </button>
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
                            <button
                                type="submit"
                                :disabled="processing"
                                class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium whitespace-nowrap text-primary-foreground ring-offset-background transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                            >
                                <span>{{
                                    processing ? 'Authorizing...' : 'Authorize'
                                }}</span>

                                <svg
                                    v-show="processing"
                                    class="mr-3 -ml-1 h-4 w-4 animate-spin text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
