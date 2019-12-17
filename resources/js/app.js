/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

Vue.component('media-clerk', require('./components/MediaClerk.vue').default);

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

Vue.directive("pan", {
	bind: function(el, binding) {
		if (typeof binding.value === "function") {
			const mc = new Hammer(el);
			mc.get("pan").set({ direction: Hammer.DIRECTION_ALL });
			mc.on("pan", binding.value);
		}
	}
});

Vue.directive("tap", {
	bind: function(el, binding) {
		if (typeof binding.value === "function") {
			const mc = new Hammer(el);
			mc.on("tap", binding.value);
		}
	}
});

Vue.directive("press", {
	bind: function(el, binding) {
		if (typeof binding.value === "function") {
			const mc = new Hammer(el);
			mc.on("press", binding.value);
		}
	}
});

Vue.directive("press-pan-pressup", {
	bind: function(el, binding) {
		if (typeof binding.value === "function") {
			const mc = new Hammer(el);
			mc.on("press pan pressup", binding.value);
		}
	}
});

Vue.directive("pressup", {
	bind: function(el, binding) {
		if (typeof binding.value === "function") {
			const mc = new Hammer(el);
			mc.on("pressup", binding.value);
		}
	}
});


Vue.directive("swipe", {
	bind: function(el, binding) {
		if (typeof binding.value === "function") {
			const mc = new Hammer(el);
			mc.on("swipe", binding.value);
		}
	}
});

const app = new Vue({
    el: '#app',
});

window.app = app;