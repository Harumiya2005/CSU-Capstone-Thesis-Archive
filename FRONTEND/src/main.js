import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router' // <-- Add this line to import the folder you just made

const app = createApp(App)
app.use(router) // <-- Add this line to tell Vue to use it
app.mount('#app')