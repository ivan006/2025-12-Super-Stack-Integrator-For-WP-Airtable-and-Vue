<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated>
      <q-toolbar>
        <q-toolbar-title> Cache Manager </q-toolbar-title>
        <q-btn flat @click="drawerLeft = !drawerLeft" round dense icon="menu" />
      </q-toolbar>
    </q-header>

    <q-drawer show-if-above bordered v-model="drawerLeft">
      <q-list padding>
        <q-item clickable to="/data-cache-info">
          <q-item-section> Data Cache (How It Works) </q-item-section>
        </q-item>

        <q-item clickable to="/data-cache-binder">
          <q-item-section> Data Cache (Page Binder) </q-item-section>
        </q-item>

        <q-item clickable to="/html-cache-pages">
          <q-item-section> HTML Cache (Page List) </q-item-section>
        </q-item>

        <q-item
          clickable
          to="/data-acceptance-page/source-id/none/target-id/none"
        >
          <q-item-section> Data Acceptance </q-item-section>
        </q-item>
      </q-list>
    </q-drawer>

    <q-page-container>
      <div class="container-lg">
        <router-view />
      </div>
    </q-page-container>
  </q-layout>
</template>

<script>
export default {
  name: "GlobalController",
  data() {
    return {
      drawerLeft: false,
    };
  },
};
</script>
