<template>
    <q-page class="q-pa-md">

        <!-- Title -->
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

        <!-- Tabs -->
        <q-tabs v-model="activeTab" dense class="text-grey" active-color="primary" indicator-color="primary">
            <q-tab name="pages" label="Pages" />
            <q-tab v-for="tab in sitemapTabs" :key="tab.name" :name="tab.name" :label="tab.label" />
        </q-tabs>

        <q-separator />

        <q-tab-panels v-model="activeTab" animated>

            <!-- PAGES TAB -->
            <q-tab-panel name="pages">

                <q-card flat bordered class="q-mb-md">
                    <q-card-section>
                        <div class="text-subtitle1 q-mb-sm">Pages</div>

                        <q-option-group v-model="selected" type="checkbox" :options="pageOptions" dense />

                        <div class="text-caption text-grey q-mt-sm">
                            Homepage is always available.
                            Other pages are loaded from <code>pages.json</code>.
                        </div>
                    </q-card-section>
                </q-card>

            </q-tab-panel>

            <!-- SITEMAP TABS -->
            <q-tab-panel v-for="tab in sitemapTabs" :key="tab.name" :name="tab.name">

                <q-card flat bordered class="q-mb-md">
                    <q-card-section>

                        <div class="text-subtitle1 q-mb-sm">
                            Sitemap: {{ tab.label }}
                        </div>

                        <q-option-group v-model="tab.selected" type="checkbox" :options="tab.options" dense />

                        <div class="text-caption text-grey q-mt-sm">
                            URLs loaded lazily when this tab is opened.
                        </div>

                    </q-card-section>
                </q-card>

            </q-tab-panel>

        </q-tab-panels>

        <!-- Actions -->
        <q-card flat bordered class="q-mb-md">
            <q-card-section>
                <div class="row q-gutter-sm">
                    <q-btn label="Cache Selected" color="positive" unelevated :loading="loading"
                        @click="cacheSelected" />
                    <q-btn label="Delete Selected" color="negative" flat :loading="loading" @click="deleteSelected" />
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
            loading: false,
            status: '',
            currentUrl: '',
            activeTab: 'pages',

            pageOptions: [
                { label: 'Homepage', value: '' }
            ],
            selected: [],

            sitemapTabs: []
        }
    },

    watch: {
        activeTab(tab) {
            const sitemap = this.sitemapTabs.find(t => t.name === tab)
            if (sitemap && !sitemap.loaded) {
                this.loadSitemapTab(sitemap)
            }
        }
    },

    mounted() {
        this.loadPages()
        this.loadSitemapsConfig()
    },

    methods: {
        cacheBase() {
            return import.meta.env.VITE_CACHE_BASE || ''
        },

        async post(payload) {
            const body = new URLSearchParams(payload)
            return fetch(`${this.cacheBase()}/html-cache/index.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body
            }).then(r => r.text())
        },

        /* ------------------ PAGES ------------------ */

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

        /* ------------------ SITEMAPS ------------------ */

        async loadSitemapsConfig() {
            try {
                const res = await fetch(`${this.cacheBase()}/html-cache/sitemaps.json`)
                const json = await res.json()

                json.sitemaps.forEach((url, i) => {
                    const filename = url.split('/').pop()

                    this.sitemapTabs.push({
                        name: `sitemap-${i}`,
                        label: filename,
                        url,
                        options: [],
                        selected: [],
                        loaded: false
                    })
                })
            } catch {
                // no sitemaps.json is allowed
            }
        },

        async loadSitemapTab(tab) {
            tab.loaded = true
            this.status = `Loading sitemap: ${tab.label}`

            try {
                const xml = await fetch(tab.url).then(r => r.text())
                const doc = new DOMParser().parseFromString(xml, 'text/xml')

                // sitemap index
                const sitemapNodes = [...doc.getElementsByTagName('sitemap')]
                if (sitemapNodes.length) {
                    for (const node of sitemapNodes) {
                        const loc = node.getElementsByTagName('loc')[0]?.textContent
                        if (loc) {
                            await this.loadSubSitemap(loc, tab)
                        }
                    }
                } else {
                    // normal sitemap
                    this.parseUrlSet(doc, tab)
                }

                this.status = `✅ Loaded ${tab.options.length} URLs`
            } catch (e) {
                this.status = `❌ Failed to load sitemap`
            }
        },

        async loadSubSitemap(url, tab) {
            const xml = await fetch(url).then(r => r.text())
            const doc = new DOMParser().parseFromString(xml, 'text/xml')
            this.parseUrlSet(doc, tab)
        },

        parseUrlSet(doc, tab) {
            const urls = [...doc.getElementsByTagName('url')]
            urls.forEach(u => {
                const loc = u.getElementsByTagName('loc')[0]?.textContent
                if (!loc) return

                const slug = loc.replace(window.location.origin, '').replace(/^\/|\/$/g, '')
                tab.options.push({
                    label: `/${slug || ''}/`,
                    value: slug
                })
            })
        },

        /* ------------------ ACTIONS ------------------ */

        async backupRoot() {
            this.loading = true
            this.status = await this.post({ action: 'backup', slug: '' })
            this.loading = false
        },

        async deleteSelected() {
            const slugs = this.getSelectedSlugs()
            if (!slugs.length) return

            this.loading = true
            for (const slug of slugs) {
                this.status = await this.post({ action: 'delete', slug })
            }
            this.loading = false
        },

        async cacheSelected() {
            const slugs = this.getSelectedSlugs()
            if (!slugs.length) return

            this.loading = true
            const iframe = this.$refs.iframe
            const base = window.location.origin

            for (const slug of slugs) {
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
        },

        getSelectedSlugs() {
            if (this.activeTab === 'pages') {
                return this.selected
            }
            const tab = this.sitemapTabs.find(t => t.name === this.activeTab)
            return tab ? tab.selected : []
        }
    }
}
</script>
