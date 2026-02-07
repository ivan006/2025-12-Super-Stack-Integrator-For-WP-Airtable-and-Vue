<template>
  <q-page class="q-pa-md">
    <div class="text-h5 q-mb-md">My-Listing → Airtable Sync</div>

    <!-- SHARED ENTITY -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section>
        <div class="text-subtitle1 q-mb-sm">Entity</div>

        <q-select
          v-model="entity"
          :options="entities"
          emit-value
          map-options
          label="Entity"
          outlined
          dense
        />
      </q-card-section>
    </q-card>

    <div class="row q-col-gutter-md q-mb-md">
      <!-- SOURCE -->
      <div class="col-12 col-md-6">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-subtitle1 q-mb-sm">Source</div>

            <q-input
              v-model="source.id"
              label="Source ID"
              outlined
              dense
              class="q-mb-sm"
            />

            <q-btn
              label="Fetch Source"
              color="secondary"
              unelevated
              :loading="source.loading"
              @click="fetchSource"
            />
          </q-card-section>
        </q-card>
      </div>

      <!-- TARGET -->
      <div class="col-12 col-md-6">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-subtitle1 q-mb-sm">Target</div>

            <q-input
              v-model="target.id"
              label="Target ID"
              outlined
              dense
              class="q-mb-sm"
            />

            <q-btn
              label="Fetch Target"
              color="primary"
              unelevated
              :loading="target.loading"
              @click="fetchTarget"
            />
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- DEBUG OUTPUT -->
    <div class="row q-col-gutter-md">
      <div class="col-12 col-md-6">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-subtitle1 q-mb-sm">Source Record</div>

            <pre
              class="q-pa-sm"
              style="
                background: #111;
                color: #0f0;
                border-radius: 4px;
                min-height: 260px;
                overflow: auto;
                font-size: 12px;
              "
              >{{ source.data }}</pre
            >
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-6">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-subtitle1 q-mb-sm">Target Record</div>

            <pre
              class="q-pa-sm"
              style="
                background: #111;
                color: #0f0;
                border-radius: 4px;
                min-height: 260px;
                overflow: auto;
                font-size: 12px;
              "
              >{{ targetData }}</pre
            >
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
export default {
  name: "RevisionBridge",

  data() {
    return {
      entity: null,

      source: {
        id: "",
        data: null,
        loading: false,
      },

      target: {
        id: "",
        loading: false,
        _data: null,
      },

      entities: [],
    };
  },

  computed: {
    targetData() {
      return this.target._data;
    },
  },

  async mounted() {
    await this.loadConfigs();
  },

  methods: {
    async loadConfigs() {
      const base = import.meta.env.VITE_CACHE_BASE;
      const res = await fetch(
        `${base}/data-acceptance/index.php?endpoint=configs-fetch`,
      );
      const json = await res.json();

      this.entities = json.entities.map((e) => ({
        label: `${e.source_entity_name} → ${e.target_entity_name}`,
        value: {
          source: e.source_entity_name,
          target: e.target_entity_name,
        },
      }));
    },

    async fetchSource() {
      if (!this.entity || !this.source.id) return;
      this.source.loading = true;

      const base = import.meta.env.VITE_CACHE_BASE;
      const res = await fetch(
        `${base}/data-acceptance/index.php?endpoint=source-fetch&entity=${encodeURIComponent(
          this.entity.source,
        )}&id=${encodeURIComponent(this.source.id)}`,
      );

      this.source.data = await res.json();
      this.source.loading = false;
    },

    async fetchTarget() {
      if (!this.entity || !this.target.id) return;
      this.target.loading = true;

      const base = import.meta.env.VITE_CACHE_BASE;
      const res = await fetch(
        `${base}/data-acceptance/index.php?endpoint=target-fetch&entity=${encodeURIComponent(
          this.entity.target,
        )}&id=${encodeURIComponent(this.target.id)}`,
      );

      this.target._data = await res.json();
      this.target.loading = false;
    },
  },
};
</script>
