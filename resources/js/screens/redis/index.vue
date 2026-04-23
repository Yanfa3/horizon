<script type="text/ecmascript-6">
export default {
    /**
     * The component's data.
     */
    data() {
        return {
            ready: false,
            keys: [],
            selectedKey: null,
            selectedContent: null,
            selectedType: null,
        };
    },

    /**
     * Prepare the component.
     */
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
                    this.ready = true;
                });
        },

        showContent(key) {
            this.$http.get(Horizon.basePath + '/api/redis/' + encodeURIComponent(key.id))
                .then(response => {
                    this.selectedKey = key.id;
                    this.selectedType = response.data.type;
                    this.selectedContent = response.data.value;
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
    <div>
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span>Redis Keys</span>
                <button class="btn btn-sm btn-outline-primary" @click="loadKeys">
                    Refresh
                </button>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>
            </div>

            <div v-if="ready && keys.length === 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span>There aren't any keys.</span>
            </div>

            <table v-if="ready && keys.length > 0" class="table table-hover table-sm mb-0">
                <thead>
                <tr>
                    <th>Key</th>
                    <th class="text-right">Action</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="key in keys" :key="key.id">
                    <td>
                        <code>{{ key.id }}</code>
                    </td>
                    <td class="text-right">
                        <button class="btn btn-sm btn-outline-info" @click="showContent(key)">View</button>
                        <button class="btn btn-sm btn-outline-danger" @click="deleteKey(key)">Delete</button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="card" v-if="selectedKey">
            <div class="card-header">
                Content for: <code>{{ selectedKey }}</code> 
                <span class="badge badge-secondary ml-2">{{ selectedType }}</span>
            </div>
            <div class="card-body card-bg-secondary" style="overflow-x: auto;">
                <pre class="mb-0"><code>{{ selectedContent }}</code></pre>
            </div>
        </div>
    </div>
</template>
