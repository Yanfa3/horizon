<script type="text/ecmascript-6">
export default {
    data() {
        return {
            ready: false,
            keys: [],
            keyFilter: '',
            selectedKey: null,
            selectedContent: null,
            selectedType: null,
            loadingContent: false,
        };
    },
    computed: {
        filteredKeys() {
            if (!this.keyFilter) {
                return this.keys;
            }

            const search = this.keyFilter.toLowerCase();

            return this.keys.filter(key => key.id.toLowerCase().includes(search));
        },
        prettySelectedContent() {
            if (this.selectedContent === null || this.selectedContent === undefined) {
                return '';
            }

            if (typeof this.selectedContent === 'object') {
                return JSON.stringify(this.selectedContent, null, 2);
            }

            return String(this.selectedContent);
        },
    },
    mounted() {
        document.title = "Horizon - Redis Manager";
        this.loadKeys();
    },
    methods: {
        loadKeys() {
            this.ready = false;
            this.$http.get(Horizon.basePath + '/api/redis')
                .then(response => {
                    this.keys = response.data.keys || [];
                })
                .catch(() => {
                    this.keys = [];
                })
                .finally(() => {
                    this.ready = true;
                });
        },
        showContent(key) {
            this.loadingContent = true;
            this.$http.get(Horizon.basePath + '/api/redis/' + encodeURIComponent(key.id))
                .then(response => {
                    this.selectedKey = key.id;
                    this.selectedType = response.data.type;
                    this.selectedContent = response.data.value;
                })
                .finally(() => {
                    this.loadingContent = false;
                });
        },
        deleteKey(key) {
            if (!confirm('Are you sure you want to delete this key?')) {
                return;
            }
            this.$http.delete(Horizon.basePath + '/api/redis/' + encodeURIComponent(key.id))
                .then(() => {
                    if (this.selectedKey === key.id) {
                        this.selectedKey = null;
                        this.selectedContent = null;
                        this.selectedType = null;
                    }
                    this.loadKeys();
                });
        }
    }
}
</script>

<template>
    <div class="redis-manager row">
        <div class="col-lg-7 mb-4 mb-lg-0">
            <div class="card redis-card overflow-hidden h-100">
                <div class="card-header redis-card-header">
                    <div>
                        <h2 class="h6 m-0">Redis Keys</h2>
                        <small class="text-muted">{{ keys.length }} total</small>
                    </div>

                    <button class="btn btn-outline-secondary btn-sm redis-refresh" @click="loadKeys">
                        Refresh
                    </button>
                </div>

                <div class="card-bg-secondary px-3 py-3 border-bottom redis-toolbar">
                    <div class="w-100">
                        <input
                            v-model="keyFilter"
                            type="text"
                            class="form-control"
                            placeholder="Filter keys"
                        >
                    </div>
                </div>

                <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin fill-text-color">
                        <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                    </svg>
                </div>

                <div v-if="ready && keys.length === 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                    <span>There aren't any keys.</span>
                </div>

                <div v-if="ready && keys.length > 0 && filteredKeys.length === 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                    <span>No keys match your filter.</span>
                </div>

                <table v-if="ready && filteredKeys.length > 0" class="table table-hover mb-0 redis-keys-table">
                    <thead>
                    <tr>
                        <th>Key</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr
                        v-for="key in filteredKeys"
                        :key="key.id"
                        :class="{ 'redis-row-active': selectedKey === key.id }"
                    >
                        <td class="align-middle">
                            <span class="redis-key-label"><code>{{ key.id }}</code></span>
                        </td>
                        <td class="text-right align-middle redis-actions">
                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="showContent(key)">
                                View
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" @click="deleteKey(key)">
                                Delete
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card redis-card overflow-hidden h-100">
                <div class="card-header redis-card-header">
                    <h2 class="h6 m-0">Key Details</h2>

                    <span v-if="selectedKey" class="badge badge-secondary">{{ selectedType }}</span>
                </div>

                <div v-if="selectedKey" class="card-bg-secondary p-3 redis-selected-key">
                    {{ selectedKey }}
                </div>

                <div class="card-body card-bg-secondary redis-content-panel">
                    <div v-if="loadingContent" class="d-flex align-items-center justify-content-center py-5">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin fill-text-color">
                            <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                        </svg>
                    </div>

                    <div v-else-if="!selectedKey" class="d-flex align-items-center justify-content-center h-100 text-muted redis-empty-state">
                        Select a key to inspect its contents.
                    </div>

                    <pre v-else class="mb-0 redis-content-code"><code>{{ prettySelectedContent }}</code></pre>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.redis-manager {
    --redis-surface: color-mix(in srgb, var(--card-bg-secondary) 88%, white);
}

.redis-card {
    border: 1px solid color-mix(in srgb, var(--border-color) 80%, transparent);
    box-shadow: 0 14px 36px -30px rgba(15, 23, 42, 0.45);
}

.redis-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.redis-refresh {
    border-radius: 999px;
    padding-left: 0.9rem;
    padding-right: 0.9rem;
}

.redis-toolbar {
    background: linear-gradient(180deg, color-mix(in srgb, var(--redis-surface) 95%, white), var(--card-bg-secondary));
}

.redis-keys-table thead th {
    border-top: 0;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--muted-text-color);
}

.redis-key-label {
    display: inline-block;
    word-break: break-all;
    line-height: 1.35;
}

.redis-row-active {
    background: color-mix(in srgb, var(--muted) 8%, transparent);
}

.redis-actions {
    white-space: nowrap;
}

.redis-actions .btn + .btn {
    margin-left: 0.5rem;
}

.redis-selected-key {
    overflow-x: auto;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 0.84rem;
    border-bottom: 1px solid var(--border-color);
    word-break: break-all;
}

.redis-content-panel {
    overflow-x: auto;
    min-height: 24rem;
}

.redis-empty-state {
    min-height: 24rem;
}

.redis-content-code {
    white-space: pre-wrap;
    font-size: 0.84rem;
    line-height: 1.45;
}

@media (max-width: 991.98px) {
    .redis-content-panel,
    .redis-empty-state {
        min-height: 16rem;
    }
}
</style>
