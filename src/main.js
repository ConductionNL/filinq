import Vue from 'vue'
import { PiniaVuePlugin } from 'pinia'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import pinia from './pinia.js'
import App from './App.vue'
import './assets/app.css'
Vue.mixin({ methods: { t, n } })

Vue.use(PiniaVuePlugin)

new Vue(
    {
        pinia,
        render: h => h(App),
    },
).$mount('#content')
