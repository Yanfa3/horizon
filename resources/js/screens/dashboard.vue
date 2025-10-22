<script type="text/ecmascript-6">
import moment from 'moment';
import axios from 'axios';

export default {
    /**
     * The component's data.
     */
    data() {
        return {
            // Data for the original Horizon Overview
            stats: {},

            // Data for the new Supervisor Queues
            supervisorProcesses: [],
            apiError: false,

            ready: false,
        };
    },


    /**
     * Prepare the component.
     */
    mounted() {
        document.title = "Horizon - Dashboard";
    },


    computed: {
        // --- Computed properties for the new Supervisor functionality ---

        /**
         * The overall status based on the supervisor API health.
         * This will be used in the main overview card.
         */
        supervisorStatus() {
            if (this.apiError) {
                return 'inactive';
            }
            // Status is 'running' if the API call was successful.
            return 'running';
        },

        /**
         * The total number of supervisor processes.
         */
        totalSupervisorProcesses() {
            return this.supervisorProcesses.length;
        },

        /**
         * The supervisor queues, grouped by queue name for display.
         */
        supervisorQueues() {
            const queues = {};
            this.supervisorProcesses.forEach(process => {
                const queueName = process.group;
                if (!queues[queueName]) {
                    queues[queueName] = {
                        name: queueName,
                        processes: 0,
                        running_processes: 0,
                    };
                }
                queues[queueName].processes++;
                if (process.state === 'RUNNING') {
                    queues[queueName].running_processes++;
                }
            });
            return Object.values(queues);
        },


        // --- Computed properties for the original Horizon Overview ---

        /**
         * Determine the recent job period label.
         */
        recentJobsPeriod() {
            return !this.ready
                ? 'Jobs Past Hour'
                : `Jobs Past ${this.determinePeriod(this.stats.periods.recentJobs)}`;
        },

        /**
         * Determine the recently failed job period label.
         */
        failedJobsPeriod() {
            return !this.ready
                ? 'Failed Jobs Past 7 Days'
                : `Failed Jobs Past ${this.determinePeriod(this.stats.periods.failedJobs)}`;
        },
    },


    methods: {
        // --- Methods to fetch data ---

        /**
         * Load the supervisor processes from your custom API.
         */
        fetchSupervisorProcesses() {
            return axios.get('/supervisor/processes')
                .then(response => {
                    this.supervisorProcesses = response.data;
                    this.apiError = false;
                })
                .catch(error => {
                    console.error('Error fetching supervisor processes:', error);
                    this.apiError = true;
                });
        },

        /**
         * Load the general stats from Horizon's API for the overview.
         */
        loadStats() {
            return this.$http.get(Horizon.basePath + '/api/stats')
                .then(response => {
                    this.stats = response.data;

                    if (Object.values(response.data.wait)[0]) {
                        this.stats.max_wait_time = Object.values(response.data.wait)[0];
                        this.stats.max_wait_queue = Object.keys(response.data.wait)[0].split(':')[1];
                    }
                });
        },

        /**
         * Poll handler to refresh all data at regular intervals.
         */
        refreshStatsPeriodically() {
            Promise.all([
                this.loadStats(),
                this.fetchSupervisorProcesses(),
            ]).then(() => {
                this.ready = true;
            });
        },


        // --- Helper Methods ---

        humanTime(time) {
            return moment.duration(time, "seconds").humanize().replace(/^(.)/g, function ($1) {
                return $1.toUpperCase();
            });
        },

        determinePeriod(minutes) {
            return moment.duration(moment().diff(moment().subtract(minutes, "minutes"))).humanize().replace(/^An?\s/i, '').replace(/^(.)|\s(.)/g, function ($1) {
                return $1.toUpperCase();
            });
        }
    }
}
</script>

<template>
    <div>
        <poll @poll="refreshStatsPeriodically" :interval="5" />

        <!-- Full Original Horizon Overview Card -->
        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Overview</h2>
            </div>

            <div class="card-bg-secondary">
                <div class="d-flex">
                    <div class="w-25">
                        <div class="p-4">
                            <small class="text-muted fw-bold">Jobs Per Minute</small>
                            <p class="h4 mt-2 mb-0">{{ stats.jobsPerMinute ? stats.jobsPerMinute.toLocaleString() : 0 }}</p>
                        </div>
                    </div>
                    <div class="w-25">
                        <div class="p-4">
                            <small class="text-muted fw-bold" v-text="recentJobsPeriod"></small>
                            <p class="h4 mt-2 mb-0">{{ stats.recentJobs ? stats.recentJobs.toLocaleString() : 0 }}</p>
                        </div>
                    </div>
                    <div class="w-25">
                        <div class="p-4">
                            <small class="text-muted fw-bold" v-text="failedJobsPeriod"></small>
                            <p class="h4 mt-2 mb-0">{{ stats.failedJobs ? stats.failedJobs.toLocaleString() : 0 }}</p>
                        </div>
                    </div>
                    <div class="w-25">
                        <div class="p-4">
                            <small class="text-muted fw-bold">Status</small>
                            <!-- THIS BLOCK IS NOW POWERED BY YOUR API -->
                            <div class="d-flex align-items-center mt-2">
                                <svg v-if="supervisorStatus == 'running'" xmlns="http://www.w3.org/2000/svg" class="text-success" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg v-if="supervisorStatus == 'inactive'" xmlns="http://www.w3.org/2000/svg" class="text-danger" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>
                                <p class="h4 mb-0 ms-2">{{ {running: 'Active', inactive: 'Inactive'}[supervisorStatus] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex">
                    <div class="w-25">
                        <div class="p-4 mb-0">
                            <!-- This can now show total supervisor processes or Horizon's total -->
                            <small class="text-muted fw-bold">Total Processes</small>
                            <p class="h4 mt-2">{{ totalSupervisorProcesses.toLocaleString() }}</p>
                        </div>
                    </div>
                    <div class="w-25">
                        <div class="p-4 mb-0">
                            <small class="text-muted fw-bold">Max Wait Time</small>
                            <p class="mt-2 mb-0">{{ stats.max_wait_time ? humanTime(stats.max_wait_time) : '-' }}</p>
                            <small class="mt-1" v-if="stats.max_wait_queue">({{ stats.max_wait_queue }})</small>
                        </div>
                    </div>
                    <div class="w-25">
                        <div class="p-4 mb-0">
                            <small class="text-muted fw-bold">Max Runtime</small>
                            <p class="h4 mt-2">{{ stats.queueWithMaxRuntime ? stats.queueWithMaxRuntime : '-' }}</p>
                        </div>
                    </div>
                    <div class="w-25">
                        <div class="p-4 mb-0">
                            <small class="text-muted fw-bold">Max Throughput</small>
                            <p class="h4 mt-2">{{ stats.queueWithMaxThroughput ? stats.queueWithMaxThroughput : '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supervisor Queues List -->
        <div class="card overflow-hidden mt-4" v-if="!apiError && supervisorQueues.length">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h2 class="h6 m-0">Supervisor Queues</h2>
            </div>

            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Queue</th>
                    <th class="text-end">Total Processes</th>
                    <th class="text-end">Running Processes</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="queue in supervisorQueues" :key="queue.name">
                    <td><span>{{ queue.name }}</span></td>
                    <td class="text-end text-muted">{{ queue.processes.toLocaleString() }}</td>
                    <td class="text-end text-muted">{{ queue.running_processes.toLocaleString() }}</td>
                </tr>
                </tbody>
            </table>
        </div>

        <div v-if="apiError && ready" class="card mt-4">
            <div class="card-body text-center">
                Could not connect to the Supervisor API.
            </div>
        </div>

    </div>
</template>
