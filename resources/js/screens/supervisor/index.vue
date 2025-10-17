<template>
    <div>
        <heading class="mb-6">Processes</heading>

        <card class="overflow-hidden">
            <table class="w-full table-auto">
                <thead>
                <tr class="bg-gray-100 text-left text-xs uppercase tracking-wide">
                    <th class="px-6 py-3">Name</th>
                    <th class="px-6 py-3">Group</th>
                    <th class="px-6 py-3">State</th>
                    <th class="px-6 py-3">PID</th>
                    <th class="px-6 py-3">Uptime</th>
                    <th class="px-6 py-3">Description</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="process in processes" :key="process.name" class="border-t">
                    <td class="px-6 py-4">{{ process.name }}</td>
                    <td class="px-6 py-4">{{ process.group }}</td>
                    <td class="px-6 py-4" :class="{
                            'text-green-600 font-semibold': process.state === 'RUNNING',
                            'text-red-600 font-semibold': process.state !== 'RUNNING'
                        }">{{ process.state }}</td>
                    <td class="px-6 py-4">{{ process.pid }}</td>
                    <td class="px-6 py-4">{{ formatUptime(process.uptime) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ process.description }}</td>
                </tr>
                </tbody>
            </table>
        </card>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    data() {
        return {
            processes: [],
        };
    },

    mounted() {
        this.fetch();
    },

    methods: {
        fetch() {
            axios.get('/supervisor/processes')
                .then(response => {
                    this.processes = response.data;
                })
                .catch(error => {
                    console.error('Error fetching processes:', error);
                });
        },

        formatUptime(seconds) {
            if (seconds === 0) return '0:00:00';

            const days = Math.floor(seconds / 86400);
            seconds %= 86400;
            const hours = Math.floor(seconds / 3600);
            seconds %= 3600;
            const minutes = Math.floor(seconds / 60);
            const secs = seconds % 60;

            let str = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            if (days > 0) {
                return `${days} day${days > 1 ? 's' : ''}, ${str}`;
            }
            return str;
        },
    },
};
</script>
