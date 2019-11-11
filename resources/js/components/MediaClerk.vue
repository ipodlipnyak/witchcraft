<template>
    <div>
        <b-navbar id="nav-header" type="info" variant="dark">
        	<b-navbar-nav>
        		<b-nav-item v-for="(slide, index) in slides" :key="slide.label" href="#" v-tap="(e) => selectSlide(e, index)"
        		v-bind:class="[slide == activeSlide ? 'active' : '']">
        		<p>{{ slide.label }}</p>
        		</b-nav-item>
        	</b-navbar-nav>
        </b-navbar>
        <main>
        <section class="slider">
            <div class="slider__list" ref="list"  v-pan="onPan">
                <div v-for="(slide, index) in slides" :key="slide.label" class="slider__item">
                    <component :ref="slide.label" :api-token="apiToken" v-bind:is="slide.component"></component>
                </div>
            </div>
        </section>

        </main>
    </div>
</template>


<script>

import { NavbarPlugin } from 'bootstrap-vue'
import Uploader from './Uploader'
import Projects from './Projects'

import VueScrollSnap from './VueScrollSnap'
import VueScrollactive from 'vue-scrollactive'

import {TweenMax} from "gsap/TweenMax";

Vue.use(NavbarPlugin)



    export default {
		props: ['apiToken'],
	  	data: function () {
		    return {
		    	slides: [
		    		{
		    			label: 'uploader',
		    			component: 'uploader'
		    		},
		    		{
		    			label: 'projects',
		    			component: 'projects'
		    		},
		    		{
		    			label: 'dumdum',
		    			component: 'projects'
		    		},
		    	],
		    	
		      currentOffset: 0,
		      colors: [
					"#F7CC45",
					"#AC6909",
					"#272625",
					"#FFAD01",
					"#81DC58",
					"#C68E71",
					"#F2B2BD",
					"#FFCB00",
					"#BE9763"
		      ],
		      
		      activeSlide: '',
		    }
		},
		computed: {
			slidesOffsets() {
				let result = [];
				let self = this;
				
				self.slides.forEach(function(slide, index){
					result[index] = -100 * self.overflowRatio / self.count * index;
				});
				
				return result;
			},
			
			overflowRatio() {
				return this.$refs.list.scrollWidth / this.$refs.list.offsetWidth;
			},
			itemWidth() {
				return this.$refs.list.scrollWidth / this.slides.length;
			},
			count() {
				return this.slides.length;
			},
		},

		
		components: {
			Uploader,
			Projects,
			VueScrollSnap
		},
		
		beforeMount() {
			this.activeSlide = this.slides[0];
		},
		
		created() {
			window.addEventListener("scroll", this.handleScroll)
		},
		
        mounted() {
			//
        },
        
        destroyed () {
        	window.removeEventListener("scroll", this.handleScroll)
        },
        
        watch: {
        	currentOffset: function(newVal, oldVal) {
				let self = this;
				let bias = 10;
				let newActiveSlide = self.slides.find(function(el) {
					let index = self.slides.indexOf(el);
					let max = newVal + bias;
					let min = newVal - bias;
					return min < self.slidesOffsets[index] && self.slidesOffsets[index] < max;
				});
				
				if (newActiveSlide) {
					self.activeSlide = newActiveSlide;
				}
			},
		},
        
        methods: {
        	onPan: function(e) {
        		  // how far the slider has been dragged in percentage of the visible container
        		  const dragOffset = 100 / this.itemWidth * e.deltaX / this.slides.length * this.overflowRatio;

        		  // transforming from where the slider currently 
        		  const transform = this.currentOffset + dragOffset;

        		  // updating the transform with CSS Variables
        		  this.$refs.list.style.setProperty("--x", transform);

        		  // user stopped touching, this is the last event
        		  if (e.isFinal) {
        			  let finalOffset = 0;
        			  this.currentOffset = transform;
        		    // how far we can drag depends on how much our slider is overflowing
        		    const maxScroll = 100 - this.overflowRatio * 100;
        		    
        		    // animate to last item
        		    if (this.currentOffset <= maxScroll) {
        		      finalOffset = maxScroll;
        		    } else if (this.currentOffset >= 0) { 
        		      // animate to first item
        		      finalOffset = 0;
        		    } else {
        		    	// animate to next item according to pan direction
        		    	const index = this.currentOffset / this.overflowRatio / 100 * this.count;
        		    	const nextIndex = e.deltaX <= 0 ? Math.floor(index) : Math.ceil(index);
        		    	finalOffset = 100 * this.overflowRatio / this.count * nextIndex;
        		    }
        		    
        		    this.goToSlide(finalOffset);
        		    
        		  }
        	},
        	
        	goToSlide: function(finalOffset) {
        		// animate it!
    		    TweenMax.fromTo(this.$refs.list, 0.5,
    		      { '--x': this.currentOffset },
    		      { '--x': finalOffset,
    		        ease: Elastic.easeOut.config(1, 0.8),
    		        onComplete: () => {
    		          this.currentOffset = finalOffset;
    		        }
    		      }
    		    );
        	},
        	
        	selectSlide: function(e, slideIndex) {
        		let slide = this.slides[slideIndex]; 
        		let target = e.target.nodeName == "P" ? e.target : e.target.firstChild;
        		if (slide) {
        			TweenMax.to(target, 0.12, { scale: 1.1, yoyo: true, repeat: 1, ease: Sine.easeOut});
        			this.activeSlide = slide;
        		}
        		
        		this.goToSlide(this.slidesOffsets[slideIndex])
        	},
            handleScroll () {
            	console.log('swoop');
            },
    	}
	}
</script>

<style lang='scss' scoped>
  .item {
    min-width: 100%;
  }
 
  .scroll-snap-container {
    height: 250px;
    flex: 0 0 100%;
  }
  
  .slider {
	width: 100%;
	overflow: visible;
  	position: relative;
  	white-space: nowrap;

	&__list {
		display: flex;
		flex: 1;
		width: 100%;
		height: 100%;
		backface-visibility: hidden;
		transform: translateX(calc(var(--x, 0) * 1%));
		
	}
	
	&__item {
		position: relative;
		flex: 0 0 100%;
		
		display: flex;
		justify-content: center;
		align-items: center;
		height: 100%;
		margin-right: 12px;
		padding: 6px;
		box-sizing: border-box;
		
		border-radius: 8px;

		&:focus {
			opacity: 0.8;
		}
		
	}
}
</style>