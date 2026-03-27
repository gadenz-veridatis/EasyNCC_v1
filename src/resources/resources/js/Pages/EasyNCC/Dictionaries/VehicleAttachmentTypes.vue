<template>
  <DictionaryIndex
    type="vehicle-attachment-types"
    title="Tipologie Allegati Veicoli"
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
