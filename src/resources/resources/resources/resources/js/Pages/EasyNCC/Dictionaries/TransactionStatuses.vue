<template>
  <DictionaryIndex
    type="transaction-statuses"
    title="Stato Movimenti"
    :has-code="true"
    :has-abbreviation="true"
    :has-color="true"
    :has-transaction-type-group="true"
    :has-is-final="true"
    :has-is-active="true"
    :has-sort-order="true"
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
