<template>
<div class="fu-butt" 
	v-press-pan-pressup="stopCount"
	v-pressup="stopCount" 
	v-press="startCount">
	<label><slot></slot></label>
	<div ref="filler" class="fu-fill"></div>
</div>
</template>

<script>

import gsap from "gsap";

export default {
	  	data: function () {
	  		return {
	  			tween: ''
	  		}
	  	},
		
		mounted() {
	  		var self = this;
			this.tween = gsap.to(this.$refs.filler, 1, {
    			width: "100%",
    			paused: true,
    			ease: "elastic",
		        onComplete: () => {
		        	this.holdMe();
		        	self.tween.restart();
		        	self.tween.pause();
		        }
			});
		},
		
        methods: {
        	holdMe() {
        		this.$emit('do-it');
        	},
        	
        	startCount(e) {
        		this.tween.play();
        	},
        	
        	stopCount(e) {
        		this.tween.reverse();
        	},
        },
}

</script>

<style lang='scss' scoped>
.fu-butt {
/* 	cursor: pointer; */
	position:relative;
	display: flex;
	height: 2.5rem;
	overflow: hidden;
	font-size: .75rem;
	background-color: #6cb2eb;
	border-radius: 0;
}

.fu-butt label {
	font-size: 0.9rem;
	font-weight: 400;
	line-height: 1.6;
	color: white;
	position: absolute;
	left: 50%;
	top: 50%;
	transform: translate(-50%, -50%);
}

.fu-fill {
	margin-left: auto;
	margin-right: auto;
	display: flex;
	flex-direction: column;
	justify-content: center;
	color: #fff;
	text-align: center;
	white-space: nowrap;
	background-color: #e3342f;
	border-radius: 0;
}
</style>