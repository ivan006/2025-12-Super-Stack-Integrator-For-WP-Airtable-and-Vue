<template>
  <div>
    <!-- BACKDROP (click closes) -->
    <div
      v-show="modelValue"
      class="fixed"
      style="
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.45);
        z-index: 2000;
      "
      @click="close"
    ></div>

    <!-- MODAL WRAPPER (does NOT block backdrop) -->
    <div
      v-show="modelValue"
      class="fixed flex items-center justify-center"
      style="
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        pointer-events: none;
        z-index: 2001;
      "
    >
      <!-- MODAL CARD -->
      <q-card
        flat
        bordered
        class="bg-white"
        style="
          width: 600px;
          max-width: 95vw;
          max-height: 90vh;
          overflow: hidden;
          pointer-events: auto;
        "
        @click.stop
      >
        <slot />
      </q-card>
    </div>
  </div>
</template>

<script>
export default {
  name: "AlwaysMountedModal",
  props: {
    modelValue: { type: Boolean, required: true },
  },
  emits: ["update:modelValue"],
  methods: {
    close() {
      console.log("Backdrop clicked → closing modal"); // debug
      this.$emit("update:modelValue", false);
    },
  },
};
</script>
