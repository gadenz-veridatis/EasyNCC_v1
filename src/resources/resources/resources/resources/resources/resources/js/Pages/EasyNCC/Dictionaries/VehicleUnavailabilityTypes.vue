<template>
  <DictionaryIndex
    type="vehicle-unavailability-types"
    title="Tipologie Non Disponibilit&agrave; Veicoli"
    :has-is-active="true"
  />
</template>

<script>
import DictionaryIndex from "./Index.vue";

export default {
  components: {
    DictionaryIndex,
  },
};
</script>
