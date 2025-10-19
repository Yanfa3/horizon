<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 mb-0">Supervisor Processes</h1>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Group</th>
                        <th scope="col">State</th>
                        <th scope="col">PID</th>
                        <th scope="col">Description</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-if="!processes.length">
                        <td colspan="5" class="text-center">No processes found.</td>
                    </tr>
                    <tr v-for="process in processes" :key="process.name">
                        <td>{{ process.name }}</td>
                        <td>{{ process.group }}</td>
                        <td>
                                <span class="badge" :class="{
                                    'bg-success': process.state === 'RUNNING',
                                    'bg-danger': process.state !== 'RUNNING'
                                }">{{ process.state }}</span>
                        </td>
                        <td>{{ process.pid }}</td>
                        <td class="text-muted">{{ process.description }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
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
    },
};
</script>
