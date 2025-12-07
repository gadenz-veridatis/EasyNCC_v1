<template>
  <DictionaryIndex
    type="payment-types"
    title="Tipologie Pagamento"
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
