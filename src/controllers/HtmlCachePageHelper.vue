<template>
    <div>

        <!-- SUB TABS (only if multiple sitemaps) -->
        <q-tabs v-if="sections.length > 1" v-model="activeTab" dense class="q-mb-sm">
            <q-tab v-for="s in sections" :key="s.key" :name="s.key" :label="s.label" />
        </q-tabs>

        <q-separator v-if="sections.length > 1" />

        <!-- TAB PANELS -->
        <q-tab-panels v-model="activeTab" animated>

            <q-tab-panel v-for="section in sections" :key="section.key" :name="section.key">
                <div style="max-height: 420px; overflow-y: auto">

                    <!-- Select All -->
                    <q-item clickable @click="toggleAll(section)">
                        <q-item-section>
                            <strong>
                                {{ allSelected(section) ? 'Unselect all' : 'Select all' }}
                            </strong>
                        </q-item-section>
                    </q-item>

                    <q-separator />

                    <!-- Groups of 100 -->
                    <div v-for="(group, gIdx) in grouped(section.items)" :key="gIdx">
                        <q-item clickable class="bg-grey-2" @click="toggleGroup(section, group)">
                            <q-item-section>
                                <strong>
                                    Group {{ gIdx + 1 }}
                                    ({{ groupSelectedCount(group) }}/{{ group.length }})
                                </strong>
                            </q-item-section>
                        </q-item>

                        <q-item v-for="item in group" :key="item.value" clickable @click="toggle(item.value)">
                            <q-item-section>
                                {{ item.label }}
                            </q-item-section>
                            <q-item-section side>
                                <q-checkbox :model-value="modelValue.includes(item.value)"
                                    @update:model-value="toggle(item.value)" />
                            </q-item-section>
                        </q-item>

                        <q-separator />
                    </div>

                </div>
            </q-tab-panel>

        </q-tab-panels>

    </div>
</template>

<script>
export default {
    name: 'HtmlCachePageHelper',

    props: {
        modelValue: {
            type: Array,
            required: true
        },

        options: {
            type: Array,
            default: () => []
        },

        sitemapUrl: {
            type: String,
            default: null
        }
    },

    emits: ['update:modelValue'],

    data() {
        return {
            sections: [],
            activeTab: null
        }
    },

    async mounted() {
        if (this.sitemapUrl) {
            await this.loadSitemap(this.sitemapUrl)
        } else {
            this.sections = [{
                key: 'pages',
                label: 'Pages',
                items: this.options
            }]
        }

        this.activeTab = this.sections[0]?.key
    },

    methods: {
        emit(vals) {
            this.$emit('update:modelValue', vals)
        },

        toggle(value) {
            const set = new Set(this.modelValue)
            set.has(value) ? set.delete(value) : set.add(value)
            this.emit([...set])
        },

        grouped(items) {
            const groups = []
            for (let i = 0; i < items.length; i += 100) {
                groups.push(items.slice(i, i + 100))
            }
            return groups
        },

        allSelected(section) {
            return section.items.length &&
                section.items.every(i => this.modelValue.includes(i.value))
        },

        toggleAll(section) {
            const set = new Set(this.modelValue)
            const all = this.allSelected(section)

            section.items.forEach(i =>
                all ? set.delete(i.value) : set.add(i.value)
            )

            this.emit([...set])
        },

        toggleGroup(section, group) {
            const set = new Set(this.modelValue)
            const allInGroup = group.every(i => set.has(i.value))

            group.forEach(i =>
                allInGroup ? set.delete(i.value) : set.add(i.value)
            )

            this.emit([...set])
        },

        groupSelectedCount(group) {
            return group.filter(i => this.modelValue.includes(i.value)).length
        },

        async loadSitemap(url) {
            const xml = await fetch(url).then(r => r.text())
            const doc = new DOMParser().parseFromString(xml, 'text/xml')

            const indexNodes = [...doc.querySelectorAll('sitemap > loc')]

            // SITEMAP INDEX → MULTIPLE TABS
            if (indexNodes.length) {
                for (const n of indexNodes) {
                    await this.loadSitemap(n.textContent)
                }
                return
            }

            // NORMAL URLSET → SINGLE TAB
            const urls = [...doc.querySelectorAll('url > loc')]

            const items = urls.map((n, i) => {
                const path = new URL(n.textContent).pathname.replace(/\/$/, '')
                return {
                    label: `${i + 1}. ${path}/`,
                    value: path.replace(/^\/+/, '')
                }
            })

            this.sections.push({
                key: url,
                label: url.split('/').pop(),
                items
            })
        }
    },
    watch: {
        activeTab(newTab, oldTab) {
            if (!oldTab) return

            const oldSection = this.sections.find(s => s.key === oldTab)
            if (!oldSection) return

            const set = new Set(this.modelValue)
            oldSection.items.forEach(i => set.delete(i.value))
            this.emit([...set])
        }
    }

}
</script>
