<template>
    <q-page class="q-pa-md">

        <!-- Page Title -->
        <div class="text-h5 q-mb-sm">
            Data Cache — Page Binder
        </div>

        <!-- Intro -->
        <div class="text-body2 text-grey-7 q-mb-lg">
            Compile a full Airtable dataset into a single cached JSON file by
            fetching all paginated pages. Attachments can optionally be cached
            via the data cache proxy.
        </div>

        <!-- Configuration -->
        <q-card flat bordered class="q-mb-md">
            <q-card-section>

                <div class="text-subtitle1 q-mb-md">
                    Configuration
                </div>

                <q-input v-model="apiUrl" label="Airtable API URL" outlined dense class="q-mb-md" />

                <q-input v-model="attachmentPath" label="Attachment Path (optional)" outlined dense
                    placeholder="Attachments[0].thumbnails.large.url" class="q-mb-md" />

                <q-btn label="Start Compilation" color="primary" unelevated :loading="loading"
                    @click="startCompilation" />

            </q-card-section>
        </q-card>

        <!-- Compilation Status -->
        <q-card flat bordered class="q-mb-md">
            <q-card-section>

                <div class="text-subtitle1 q-mb-md">
                    Compilation Status
                </div>

                <div class="text-body2">
                    Pages fetched:
                    <strong>{{ pagesFetched }}</strong>
                </div>

                <div class="text-body2">
                    Attachments cached:
                    <strong>—</strong>
                </div>

                <div class="text-body2">
                    Elapsed time:
                    <strong>{{ elapsedTime }}</strong>
                </div>

                <div v-if="status" class="text-body2 q-mt-sm">
                    {{ status }}
                </div>

            </q-card-section>
        </q-card>

        <!-- Existing Bound Caches -->
        <q-card flat bordered>
            <q-card-section>

                <div class="text-subtitle1 q-mb-md">
                    Existing Bound Caches
                </div>

                <q-table flat bordered dense row-key="file" :rows="caches" :columns="columns"
                    no-data-label="No bound caches found">
                    <template v-slot:body-cell-actions="props">
                        <q-td align="right">
                            <q-btn size="sm" flat label="View" @click="viewCache(props.row.source_url)" />
                        </q-td>
                    </template>
                </q-table>

            </q-card-section>
        </q-card>

    </q-page>
</template>

<script>
export default {
    name: 'DataCacheBinder',

    data() {
        return {
            apiUrl: '',
            attachmentPath: '',
            loading: false,
            status: '',
            pagesFetched: '—',
            elapsedTime: '—',
            caches: [],
            columns: [
                { name: 'file', label: 'File', field: 'file' },
                {
                    name: 'size',
                    label: 'Size (KB)',
                    field: row => (row.size / 1024).toFixed(1),
                    align: 'right'
                },
                { name: 'created_at', label: 'Created', field: 'created_at' },
                { name: 'actions', label: 'Actions', field: 'actions', align: 'right' }
            ]
        }
    },

    mounted() {
        this.listCaches()
    },

    methods: {
        async fetchAllPages(url) {
            let records = []
            let offset = null
            let page = 1

            const start = Date.now()

            do {
                this.status = `Fetching page ${page}…`

                const pageUrl = offset
                    ? `${url}&offset=${encodeURIComponent(offset)}`
                    : url

                const res = await fetch(
                    `/data-cache/index.php?regenerate=${encodeURIComponent(pageUrl)}`
                )
                const data = await res.json()

                records.push(...(data.records || []))
                offset = data.offset
                page++

                this.pagesFetched = records.length
                this.elapsedTime = `${Math.floor((Date.now() - start) / 1000)}s`

            } while (offset)

            return records
        },

        async startCompilation() {
            if (!this.apiUrl) {
                this.status = 'Please enter an Airtable API URL.'
                return
            }

            this.loading = true
            this.status = 'Starting compilation…'
            this.pagesFetched = '0'
            this.elapsedTime = '0s'

            try {
                const start = performance.now()
                const records = await this.fetchAllPages(this.apiUrl)
                const duration = ((performance.now() - start) / 1000).toFixed(2)

                this.status = `Saving ${records.length} records…`

                await fetch(
                    `/data-cache/bound-cache.php?action=save&url=${encodeURIComponent(this.apiUrl)}`,
                    {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ records, duration })
                    }
                )

                this.status = `✅ Compilation complete (${records.length} records)`
                this.listCaches()

            } catch (e) {
                this.status = `❌ Error: ${e.message}`
            } finally {
                this.loading = false
            }
        },

        async listCaches() {
            const res = await fetch('/data-cache/bound-cache.php?action=list')
            this.caches = await res.json()
        },

        viewCache(url) {
            if (!url) return
            window.open(
                `/data-cache/bound-cache.php?action=get&url=${encodeURIComponent(url)}`,
                '_blank'
            )
        }
    }
}
</script>
