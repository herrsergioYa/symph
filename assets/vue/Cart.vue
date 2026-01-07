<template>
    <div class="order-md-last">
        <h4 class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-primary">Your cart</span>
            <span class="badge bg-primary rounded-pill">{{count}}</span>
        </h4>
        <ul class="list-group mb-3">
            <li class="list-group-item d-flex justify-content-between lh-sm" v-for="item in data.items">
                <div>
                    <h6 class="my-0">{{item.product.name}}</h6>
                    <small class="text-body-secondary">{{item.product.description}}</small>
                </div>
                <span class="text-body-secondary">${{ item.quantity * item.price }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                <span>Total (USD)</span>
                <strong>${{sum}}</strong>
            </li>
        </ul>

        <button class="w-100 btn btn-primary btn-lg" type="submit">Continue to Checkout</button>
    </div>
</template>

<script setup>
import {reactive} from "vue";
import {onMounted} from "vue";
import {computed} from "vue";

let data = reactive({
    items: [],
})

onMounted(async () => {debugger;
    let items = await $.ajax('/api/cart/list', {
        method: 'GET',
        dataType: 'json',
    });
    debugger;
    data.items = items.items;
})

let sum = computed(() => {
    let sum = 0;
    for (let item of data.items) {
        sum += item.price * item.quantity;
    }
    return sum;
})

let count = computed(() => {
    let cnt = 0;
    for (let item of data.items) {
        cnt += item.quantity;
    }
    return cnt;
})

</script>
