<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 mb-0">Supervisor Processes</h1>
        </div>

        <div v-for="(groupProcesses, groupName) in groupedProcesses" :key="groupName" class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0 font-weight-bold">{{ groupName }}</h2>
                <div class="d-flex">
                    <button @click="startGroup(groupName)" class="btn btn-sm btn-success me-2" :disabled="isBusy">
                        Start Group
                    </button>
                    <button @click="stopGroup(groupName)" class="btn btn-sm btn-warning me-2" :disabled="isBusy">
                        Stop Group
                    </button>
                    <button @click="restartGroup(groupName)" class="btn btn-sm btn-danger" :disabled="isBusy">
                        Restart Group
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th scope="col" style="width: 30%;">Name</th>
                        <th scope="col" style="width: 15%;">State</th>
                        <th scope="col" style="width: 10%;">PID</th>
                        <th scope="col" style="width: 25%;">Description</th>
                        <th scope="col" class="text-end" style="width: 20%;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="process in groupProcesses" :key="process.name">
                        <td>{{ process.name }}</td>
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

        <div v-if="!processes.length && !isBusy" class="card">
            <div class="card-body text-center">
                No processes found.
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

    computed: {
        groupedProcesses() {
            return this.processes.reduce((groups, process) => {
                const groupName = process.group;
                if (!groups[groupName]) {
                    groups[groupName] = [];
                }
                groups[groupName].push(process);
                return groups;
            }, {});
        }
    },

    mounted() {
        this.fetch();
    },

    methods: {
        fetch() {
            this.isBusy = true;
            axios.get('/supervisor/processes')
                .then(response => {
                    this.processes = response.data;
                })
                .catch(error => {
                    console.error('Error fetching processes:', error);
                    alert('Could not fetch process data. Check the connection to Supervisor.');
                })
                .finally(() => {
                    this.isBusy = false;
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
                    this.isBusy = false;
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

        startGroup(groupName) {
            this.performAction(
                '/supervisor/group/start',
                { group_name: groupName },
                `Are you sure you want to start all processes in the group "${groupName}"?`
            );
        },
        stopGroup(groupName) {
            this.performAction(
                '/supervisor/group/stop',
                { group_name: groupName },
                `Are you sure you want to stop all processes in the group "${groupName}"?`
            );
        },
        restartGroup(groupName) {
            this.performAction(
                '/supervisor/group/restart',
                { group_name: groupName },
                `Are you sure you want to restart all processes in the group "${groupName}"?`
            );
        },
    },
};
</script>
