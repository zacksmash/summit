<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
/* @chisel-teams */
import type { Team } from '@/types';
/* @end-chisel-teams */

type Props = {
    user: User;
    showEmail?: boolean;
    /* @chisel-teams */
    team?: Team | null;
    /* @end-chisel-teams */
};

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
    /* @chisel-teams */
    team: null,
    /* @end-chisel-teams */
});

const { getInitials } = useInitials();

const showAvatar = computed(
    () => props.user.avatar && props.user.avatar !== '',
);
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
        <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
        <AvatarFallback class="rounded-lg text-black dark:text-white">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>
        <!-- @chisel-teams -->
        <span v-if="team" class="truncate text-xs text-muted-foreground">{{
            team.name
        }}</span>
        <!-- @end-chisel-teams -->
        <span
            v-else-if="showEmail"
            class="truncate text-xs text-muted-foreground"
            >{{ user.email }}</span
        >
    </div>
</template>
