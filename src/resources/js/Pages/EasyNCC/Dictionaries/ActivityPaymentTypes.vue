<template>
  <DictionaryIndex
    type="activity-payment-types"
    title="Tipologie Pagamento Soste"
    :has-code="true"
    :has-color="true"
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
