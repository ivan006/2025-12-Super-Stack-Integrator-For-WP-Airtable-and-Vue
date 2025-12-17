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

                <div class="row q-col-gutter-md q-mb-md">
                    <div class="col-12 col-md-8">
                        <q-input v-model="apiUrl" label="Airtable API URL" outlined dense />
                    </div>

                    <div class="col-12 col-md-4">
                        <q-input v-model="attachmentPath" label="Attachment Path (optional)" outlined dense
                            placeholder="Attachments[0].thumbnails.large.url" />
                    </div>
                </div>


                <q-btn label="Start Compilation" color="primary" unelevated :loading="loading"
                    @click="startCompilation" />

            </q-card-section>
        </q-card>

        <!-- Existing Bound Caches -->
        <q-card flat bordered class="q-mb-md">
            <q-card-section>

                <div class="text-subtitle1 q-mb-md">
                    Existing Bound Caches
                </div>

                <q-table flat bordered dense row-key="file" :rows="caches" :columns="columns"
                    no-data-label="No bound caches found" table-style="table-layout: fixed; width: 100%;" hide-bottom>
                    <template v-slot:body-cell-actions="props">
                        <q-td align="right">
                            <q-btn size="sm" flat label="View" @click="viewCache(props.row.source_url)" />
                            <q-btn size="sm" flat color="negative" label="Delete" @click="deleteCache(props.row)" />
                        </q-td>
                    </template>

                </q-table>

            </q-card-section>
        </q-card>
        <div class="row q-col-gutter-md q-mb-md">


            <div class="col-12 col-md-6">


                <!-- Compilation Status -->
                <q-card flat bordered class="q-mb-md">
                    <q-card-section>
                        <div class="text-subtitle1 q-mb-md">
                            Compilation Status
                        </div>

                        <div class="row q-col-gutter-md items-center text-body2">
                            <div class="col-auto">
                                Pages fetched: <strong>{{ pagesFetched }}</strong>
                            </div>

                            <div class="col-auto">
                                Attachments cached: <strong>—</strong>
                            </div>

                            <div class="col-auto">
                                Elapsed time: <strong>{{ elapsedTime }}</strong>
                            </div>



                            <div v-if="status" class="col-auto">
                                {{ status }}
                            </div>
                        </div>
                    </q-card-section>

                </q-card>

            </div>
            <div class="col-12 col-md-6">

                <!-- Attachment Progress -->
                <q-card flat bordered class="q-mb-md">
                    <q-card-section>
                        <div class="text-subtitle1 q-mb-sm">Attachment Progress</div>

                        <q-linear-progress :value="attachmentProgress" color="green" stripe class="q-mb-sm" />

                        <div class="text-caption">
                            {{ attachmentLoaded }} / {{ attachmentTotal }} attachments
                        </div>
                    </q-card-section>
                </q-card>
            </div>
        </div>



        <!-- Attachment Grid -->
        <q-card flat bordered v-if="attachmentImages.length">
            <q-card-section>
                <div class="row q-col-gutter-sm">
                    <div v-for="(img, i) in attachmentImages" :key="i" class="col-auto">
                        <img :src="img" style="width:120px;height:120px;object-fit:cover;border-radius:6px" />
                    </div>
                </div>
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
            columns: [{
                name: 'source_url',
                label: 'Source URL',
                field: row => row.source_url || row.file,
                style: 'white-space: normal; word-break: break-all;',
            },
            {
                name: 'size',
                label: 'Size (KB)',
                field: row => (row.size / 1024).toFixed(1),
                align: 'right'
            },
            {
                name: 'created_at',
                label: 'Created',
                field: 'created_at'
            },
            {
                name: 'actions',
                label: 'Actions',
                align: 'right',
                style: 'width: 120px;'
            },

            ],
            attachmentImages: [],
            attachmentTotal: 0,
            attachmentLoaded: 0,
            attachmentProgress: 0,


        }
    },

    mounted() {
        this.listCaches()
    },

    methods: {
        async deleteCache(row) {
            if (!row?.file) return

            if (!confirm(`Delete bound cache?\n\n${row.source_url || row.file}`)) {
                return
            }

            this.status = `🗑️ Deleting cache…`
            this.loading = true

            try {
                const CACHE_BASE = import.meta.env.VITE_CACHE_BASE || ''
                const res = await fetch(
                    `${CACHE_BASE}/data-cache/bound-cache.php?action=delete&file=${encodeURIComponent(row.file)}`
                )
                const data = await res.json()

                if (data.deleted) {
                    this.status = '✅ Cache deleted'
                    await this.listCaches()
                } else {
                    this.status = '⚠️ Could not delete cache'
                }
            } catch (e) {
                this.status = `❌ Error deleting cache: ${e.message}`
            } finally {
                this.loading = false
            }
        },
        async touchAttachments(records, attachmentPath) {
            if (!attachmentPath) return

            const parts = attachmentPath.split('.')
            const urls = []

            // extract URLs (unchanged logic)
            records.forEach(r => {
                let val = r.fields
                for (const p of parts) {
                    const m = p.match(/^(.+)\[(\d+)\]$/)
                    if (m) {
                        val = Array.isArray(val?.[m[1]]) ? val[m[1]][+m[2]] : null
                    } else {
                        val = val?.[p]
                    }
                }

                if (Array.isArray(val)) {
                    val.forEach(v => urls.push(v.url || v))
                } else if (typeof val === 'string') {
                    urls.push(val)
                }
            })

            this.attachmentImages = []
            this.attachmentTotal = urls.length
            this.attachmentLoaded = 0
            this.attachmentProgress = 0

            const CACHE_BASE = import.meta.env.VITE_CACHE_BASE || ''

            // 🔥 PARALLEL IMAGE TOUCHING
            const promises = urls.map(url => {
                return new Promise(resolve => {
                    const proxied = `${CACHE_BASE}/data-cache/index.php?url=${encodeURIComponent(url)}`
                    const img = new Image()
                    img.src = proxied

                    img.onload = img.onerror = () => {
                        this.attachmentLoaded++
                        this.attachmentProgress =
                            this.attachmentLoaded / this.attachmentTotal
                        this.attachmentImages.unshift(proxied)
                        resolve()
                    }
                })
            })

            // wait for all (optional – remove if you want pure fire-and-forget)
            await Promise.all(promises)
        },
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

                const CACHE_BASE = import.meta.env.VITE_CACHE_BASE || ''
                const res = await fetch(
                    `${CACHE_BASE}/data-cache/index.php?regenerate=${encodeURIComponent(pageUrl)}`
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
            this.status = 'Checking for existing bound cache…'
            this.pagesFetched = 0
            this.elapsedTime = '0s'

            try {
                const CACHE_BASE = import.meta.env.VITE_CACHE_BASE || ''

                // 🔹 CHECK EXISTING BOUND CACHE (minimal)
                const list = await fetch(
                    `${CACHE_BASE}/data-cache/bound-cache.php?action=list`
                ).then(r => r.json())

                const hashBuffer = await crypto.subtle.digest(
                    'SHA-256',
                    new TextEncoder().encode(this.apiUrl)
                )
                const hashHex = [...new Uint8Array(hashBuffer)]
                    .map(b => b.toString(16).padStart(2, '0'))
                    .join('')
                const filename = `bound-${hashHex}.json`

                const existing = list.find(c => c.file === filename)

                let records

                if (existing) {
                    // ✅ USE EXISTING CACHE
                    this.status = 'Using existing bound cache…'
                    const res = await fetch(
                        `${CACHE_BASE}/data-cache/bound-cache.php?action=get&url=${encodeURIComponent(this.apiUrl)}`
                    )
                    const data = await res.json()
                    records = data.records || []
                    this.pagesFetched = records.length
                } else {
                    // 🔹 ORIGINAL FLOW (UNCHANGED)
                    this.status = 'Starting compilation…'
                    const start = Date.now()

                    records = await this.fetchAllPages(this.apiUrl)

                    const duration = ((Date.now() - start) / 1000).toFixed(2)

                    await fetch(
                        `${CACHE_BASE}/data-cache/bound-cache.php?action=save&url=${encodeURIComponent(this.apiUrl)}`,
                        {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ records, duration })
                        }
                    )
                }

                // 🔹 ATTACHMENTS (UNCHANGED)
                if (this.attachmentPath) {
                    this.status = 'Caching attachments…'
                    await this.touchAttachments(records, this.attachmentPath)
                }

                this.status = `✅ Done (${records.length} records)`
                this.listCaches()

            } catch (e) {
                this.status = `❌ Error: ${e.message}`
            } finally {
                this.loading = false
            }
        },

        async listCaches() {
            const CACHE_BASE = import.meta.env.VITE_CACHE_BASE || ''
            const res = await fetch(`${CACHE_BASE}/data-cache/bound-cache.php?action=list`)
            this.caches = await res.json()
        },

        viewCache(url) {
            const CACHE_BASE = import.meta.env.VITE_CACHE_BASE || ''
            if (!url) return
            window.open(
                `${CACHE_BASE}/data-cache/bound-cache.php?action=get&url=${encodeURIComponent(url)}`,
                '_blank'
            )
        }
    }
}
</script>
