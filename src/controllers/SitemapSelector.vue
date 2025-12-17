<template>
  <div>

    <!-- Sub-sitemap tabs (only if index) -->
    <q-tabs
      v-if="isIndex"
      v-model="activeSub"
      dense
      class="text-grey q-mb-md"
      active-color="primary"
      indicator-color="primary"
    >
      <q-tab
        v-for="(s, i) in subSitemaps"
        :key="i"
        :name="i"
        :label="s.title"
      />
    </q-tabs>

    <!-- Single sitemap fallback -->
    <div class="text-subtitle1 q-mb-sm" v-else>
      {{ title }}
    </div>

    <!-- Select all -->
    <q-checkbox
      v-if="paths.length"
      :model-value="allSelected"
      label="Select all"
      class="q-mb-md"
      @update:model-value="toggleAll"
    />

    <!-- Groups -->
    <div
      v-for="group in groups"
      :key="group.index"
      class="q-mb-md"
    >
      <div class="row items-center q-mb-xs">
        <div class="text-caption">
          Group {{ group.index + 1 }}
          ({{ group.start }}–{{ group.end }})
        </div>

        <q-space />

        <q-btn
          size="sm"
          flat
          label="Select group"
          @click="toggleGroup(group)"
        />
      </div>

      <q-option-group
        v-model="localSelected"
        type="checkbox"
        dense
        :options="group.items.map((p, i) => ({
          label: `${group.start + i}. ${p}`,
          value: p
        }))"
      />
    </div>

    <div v-if="loading" class="text-caption text-grey">
      Loading sitemap…
    </div>

  </div>
</template>

<script>
export default {
  name: 'SitemapSelector',

  props: {
    sitemapUrl: { type: String, required: true }
  },

  emits: ['update:selected'],

  data () {
    return {
      isIndex: false,
      subSitemaps: [],
      activeSub: 0,
      paths: [],
      localSelected: [],
      loading: false
    }
  },

  computed: {
    title () {
      return this.sitemapUrl.split('/').pop()
    },

    allSelected () {
      return this.paths.length &&
        this.localSelected.length === this.paths.length
    },

    groups () {
      const size = 10
      const out = []
      for (let i = 0; i < this.paths.length; i += size) {
        out.push({
          index: out.length,
          start: i + 1,
          end: i + Math.min(size, this.paths.length - i),
          items: this.paths.slice(i, i + size)
        })
      }
      return out
    }
  },

  watch: {
    localSelected () {
      this.$emit('update:selected', this.localSelected)
    },

    activeSub () {
      if (this.isIndex) {
        this.loadSubSitemap(this.activeSub)
      }
    }
  },

  mounted () {
    this.loadRoot()
  },

  methods: {
    normalize (url) {
      try {
        const p = new URL(url).pathname
        return p.endsWith('/') ? p : p + '/'
      } catch {
        return null
      }
    },

    async loadRoot () {
      this.loading = true

      const res = await fetch(this.sitemapUrl)
      const xml = await res.text()
      const doc = new DOMParser().parseFromString(xml, 'text/xml')

      const indexNodes = [...doc.querySelectorAll('sitemap > loc')]

      if (indexNodes.length) {
        this.isIndex = true
        this.subSitemaps = indexNodes.map(loc => ({
          url: loc.textContent,
          title: loc.textContent.split('/').pop(),
          loaded: false,
          paths: []
        }))
        await this.loadSubSitemap(0)
      } else {
        this.paths = this.extractPaths(doc)
      }

      this.loading = false
    },

    extractPaths (doc) {
      return [...doc.querySelectorAll('url > loc')]
        .map(n => this.normalize(n.textContent))
        .filter(Boolean)
    },

    async loadSubSitemap (index) {
      const s = this.subSitemaps[index]
      if (s.loaded) {
        this.paths = s.paths
        return
      }

      this.loading = true
      const res = await fetch(s.url)
      const xml = await res.text()
      const doc = new DOMParser().parseFromString(xml, 'text/xml')

      s.paths = this.extractPaths(doc)
      s.loaded = true
      this.paths = s.paths

      this.loading = false
    },

    toggleAll () {
      this.localSelected = this.allSelected ? [] : [...this.paths]
    },

    toggleGroup (group) {
      const allIn = group.items.every(p =>
        this.localSelected.includes(p)
      )

      this.localSelected = allIn
        ? this.localSelected.filter(p => !group.items.includes(p))
        : Array.from(new Set([...this.localSelected, ...group.items]))
    }
  }
}
</script>
