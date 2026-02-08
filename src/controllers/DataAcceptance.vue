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
              v-model="sourceId"
              label="Source ID"
              outlined
              dense
              class="q-mb-sm"
            />

            <q-btn
              :disable="!entity || !sourceId"
              label="Fetch Source"
              color="secondary"
              unelevated
              :loading="source.loading"
              @click="fetchSource"
            />

            <q-btn
              :disable="!entity || !sourceId"
              label="Forward Sync"
              color="positive"
              unelevated
              class="q-ml-sm"
              :loading="source.loading"
              @click="forwardSync"
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
              v-model="targetId"
              label="Target ID"
              outlined
              dense
              class="q-mb-sm"
            />
            <q-btn
              :disable="!entity || !targetId"
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
        data: null,
        loading: false,
      },

      target: {
        loading: false,
        _data: null,
      },

      entities: [],
    };
  },

  computed: {
    sourceId: {
      get() {
        return this.$route.params.sourceId === "none"
          ? ""
          : this.$route.params.sourceId || "";
      },
      set(val) {
        this.$router.replace({
          params: {
            ...this.$route.params,
            sourceId: val && val !== "" ? String(val) : "none",
          },
        });
      },
    },

    targetId: {
      get() {
        return this.$route.params.targetId === "none"
          ? ""
          : this.$route.params.targetId || "";
      },
      set(val) {
        this.$router.replace({
          params: {
            ...this.$route.params,
            targetId: val && val !== "" ? String(val) : "none",
          },
        });
      },
    },

    targetData() {
      return this.target._data;
    },
  },

  async mounted() {
    await this.loadConfigs();
  },

  methods: {
    async forwardSync() {
      if (!this.entity || this.sourceId === "none") return;
      this.source.loading = true;

      const base = import.meta.env.VITE_CACHE_BASE;

      const params = new URLSearchParams({
        endpoint: "source-fetch-and-sync-with-create-or-update",
        entity: this.entity.source,
        id: this.sourceId,
      });

      if (this.targetId !== "none") {
        params.set("target_id", this.targetId);
      }

      const res = await fetch(
        `${base}/data-acceptance/index.php?${params.toString()}`,
      );

      const json = await res.json();

      // hydrate both sides from authoritative response
      this.source.data = json.source ?? this.source.data;
      this.target._data = json.target ?? json;

      // if a new target was created, lock it into the route
      if (json.target_id) {
        this.targetId = json.target_id;
      }

      this.source.loading = false;
    },

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
      if (!this.entity || this.sourceId === "none") return;
      this.source.loading = true;

      const base = import.meta.env.VITE_CACHE_BASE;
      const res = await fetch(
        `${base}/data-acceptance/index.php?endpoint=source-fetch&entity=${encodeURIComponent(
          this.entity.source,
        )}&id=${encodeURIComponent(this.sourceId)}`,
      );

      this.source.data = await res.json();
      this.source.loading = false;
    },

    async fetchTarget() {
      if (!this.entity || this.targetId === "none") return;
      this.target.loading = true;

      const base = import.meta.env.VITE_CACHE_BASE;
      const res = await fetch(
        `${base}/data-acceptance/index.php?endpoint=target-fetch&entity=${encodeURIComponent(
          this.entity.target,
        )}&id=${encodeURIComponent(this.targetId)}`,
      );

      this.target._data = await res.json();
      this.target.loading = false;
    },
  },
};
</script>
