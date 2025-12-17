<template>
    <q-page class="q-pa-md">

        <div class="text-h5 q-mb-sm">
            HTML Cache — Static Page Freezer
        </div>

        <div class="text-body2 text-grey-7 q-mb-lg">
            Freeze Vue-rendered pages into static HTML files for SEO.
            Pages are rendered locally and saved as <code>/slug/index.html</code>.
        </div>

        <!-- Backup -->
        <q-card flat bordered class="q-mb-md">
            <q-card-section>
                <div class="text-subtitle1 q-mb-sm">Backup</div>
                <q-btn label="Backup Root Index" color="secondary" unelevated :loading="loading" @click="backupRoot" />
            </q-card-section>
        </q-card>

        <!-- Pages -->
        <q-card flat bordered class="q-mb-md">
            <q-card-section>
                <div class="text-subtitle1 q-mb-sm">Pages</div>

                <q-option-group v-model="selected" type="checkbox" :options="pageOptions" dense />

                <div class="text-caption text-grey q-mt-sm">
                    Homepage is always available. Other pages are loaded from <code>pages.json</code>.
                </div>
            </q-card-section>
        </q-card>

        <!-- Actions -->
        <q-card flat bordered class="q-mb-md">
            <q-card-section>
                <div class="row q-gutter-sm">
                    <q-btn label="Cache Selected" color="positive" unelevated :loading="loading" @click="cachePages" />
                    <q-btn label="Delete Selected" color="negative" flat :loading="loading" @click="deletePages" />
                </div>
            </q-card-section>
        </q-card>

        <!-- Status -->
        <q-card flat bordered class="q-mb-md">
            <q-card-section>
                <div class="text-subtitle1 q-mb-sm">Status</div>

                <div class="text-body2">
                    Target URL:
                    <strong>{{ currentUrl || '—' }}</strong>
                </div>

                <div class="text-body2 q-mt-sm" v-if="status">
                    {{ status }}
                </div>
            </q-card-section>
        </q-card>

        <!-- Preview -->
        <q-card flat bordered>
            <q-card-section>
                <iframe ref="iframe" class="full-width" style="height:500px;border:1px solid #ccc" />
            </q-card-section>
        </q-card>

    </q-page>
</template>

<script>
export default {
    name: 'HtmlCachePages',

    data() {
        return {
            pageOptions: [
                { label: 'Homepage', value: '' }
            ],
            selected: [],
            loading: false,
            status: '',
            currentUrl: ''
        }
    },

    mounted() {
        this.loadPages()
    },

    methods: {
        cacheBase() {
            return import.meta.env.VITE_CACHE_BASE || ''
        },

        async loadPages() {
            try {
                const res = await fetch(`${this.cacheBase()}/html-cache/pages.json`)
                const pages = await res.json()

                pages.forEach(slug => {
                    const clean = slug.replace(/^\/+|\/+$/g, '')
                    this.pageOptions.push({
                        label: `/${clean}/`,
                        value: clean
                    })
                })
            } catch {
                this.status = '❌ Failed to load pages.json'
            }
        },

        async post(payload) {
            const body = new URLSearchParams(payload)
            return fetch(`${this.cacheBase()}/html-cache/index.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body
            }).then(r => r.text())
        },

        async backupRoot() {
            this.loading = true
            this.status = await this.post({ action: 'backup', slug: '' })
            this.loading = false
        },

        async deletePages() {
            if (!this.selected.length) return

            this.loading = true
            for (const slug of this.selected) {
                this.status = await this.post({ action: 'delete', slug })
            }
            this.loading = false
        },

        async cachePages() {
            if (!this.selected.length) return

            this.loading = true
            const iframe = this.$refs.iframe
            const base = window.location.origin

            for (const slug of this.selected) {
                const url = slug ? `${base}/${slug}/` : `${base}/`
                this.currentUrl = url
                iframe.src = url

                await new Promise(resolve => {
                    iframe.onload = async () => {
                        setTimeout(async () => {
                            try {
                                const html = iframe.contentDocument.documentElement.outerHTML
                                const encoded = btoa(unescape(encodeURIComponent(html)))
                                this.status = await this.post({
                                    action: 'save',
                                    slug,
                                    html: encoded
                                })
                            } catch {
                                this.status = '❌ Iframe access blocked (same-origin required)'
                            }
                            resolve()
                        }, 1000)
                    }
                })
            }

            this.loading = false
        }
    }
}
</script>
