<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 mb-0">Supervisor Processes</h1>
            <div class="d-flex" v-if="processes.length > 0">
                <button @click="startAllProcesses" class="btn btn-sm btn-success me-2" :disabled="isBusy">
                    Start All
                </button>
                <button @click="stopAllProcesses" class="btn btn-sm btn-warning me-2" :disabled="isBusy">
                    Stop All
                </button>
                <button @click="restartAllProcesses" class="btn btn-sm btn-danger" :disabled="isBusy">
                    Restart All
                </button>
            </div>
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
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-if="!processes.length">
                        <td colspan="6" class="text-center">No processes found.</td>
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
                        <td class="text-end">
                            <button v-if="process.state !== 'RUNNING'"
                                    @click="startProcess(process)"
                                    class="btn btn-sm btn-success" :disabled="isBusy">
                                Start
                            </button>
                            <template v-if="process.state === 'RUNNING'">
                                <div class="d-flex justify-content-end">
                                    <button @click="stopProcess(process)"
                                            class="btn btn-sm btn-warning me-2" :disabled="isBusy">
                                        Stop
                                    </button>
                                    <button @click="restartProcess(process)"
                                            class="btn btn-sm btn-danger" :disabled="isBusy">
                                        Restart
                                    </button>
                                </div>
                            </template>
                        </td>
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
            isBusy: false,
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
                    alert('Could not fetch process data. Check the connection to Supervisor.');
                });
        },

        performAction(url, payload, confirmMessage) {
            if (!confirm(confirmMessage)) {
                return;
            }
            this.isBusy = true;
            axios.post(url, payload)
                .then(response => {
                    console.log(response.data.message);
                    setTimeout(() => this.fetch(), 500);
                })
                .catch(error => {
                    const errorMessage = error.response?.data?.message || error.message;
                    console.error('An error occurred:', errorMessage);
                    alert(`Action failed: ${errorMessage}`);
                })
                .finally(() => {
                    setTimeout(() => this.isBusy = false, 700);
                });
        },

        startProcess(process) {
            this.performAction(
                '/supervisor/process/start',
                { process_name: `${process.group}:${process.name}` },
                `Are you sure you want to start the process "${process.name}"?`
            );
        },
        stopProcess(process) {
            this.performAction(
                '/supervisor/process/stop',
                { process_name: `${process.group}:${process.name}` },
                `Are you sure you want to stop the process "${process.name}"?`
            );
        },
        restartProcess(process) {
            this.performAction(
                '/supervisor/process/restart',
                { process_name: `${process.group}:${process.name}` },
                `Are you sure you want to restart the process "${process.name}"?`
            );
        },

        startAllProcesses() {
            this.performAction('/supervisor/action/start-all', {}, 'Are you sure you want to start ALL processes?');
        },
        stopAllProcesses() {
            this.performAction('/supervisor/action/stop-all', {}, 'Are you sure you want to stop ALL processes?');
        },
        restartAllProcesses() {
            this.performAction('/supervisor/action/restart-all', {}, 'Are you sure you want to restart ALL processes?');
        },
    },
};
</script>
