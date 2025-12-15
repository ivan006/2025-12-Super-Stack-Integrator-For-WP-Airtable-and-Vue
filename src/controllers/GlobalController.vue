<template>
  <q-layout view="lHh Lpr lFf">

    <q-header elevated>
      <q-toolbar>
        <q-toolbar-title>
          Cache Manager
        </q-toolbar-title>
      </q-toolbar>
    </q-header>

    <q-drawer show-if-above bordered>
      <q-list padding>

        <q-item clickable to="/data-cache-info">
          <q-item-section>
            Data Cache (How It Works)
          </q-item-section>
        </q-item>

        <q-item clickable to="/data-cache-binder">
          <q-item-section>
            Data Cache (Page Binder)
          </q-item-section>
        </q-item>

        <q-item clickable to="/html-cache-pages">
          <q-item-section>
            HTML Cache (Page List)
          </q-item-section>
        </q-item>

      </q-list>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>

  </q-layout>
</template>

<script>
export default {
  name: 'GlobalController'
}
</script>
