<template>

<div class="slider__list" ref="list"  v-pan="onPan">
	<div v-for="(slide, index) in slides" :key="slide.label"
		v-tap="(e) => onTap(e, slide)"
		class="slider__item"
		:style="{backgroundColor: colors[index]}">
		<component :ref="slide.label" :api-token="apiToken" v-bind:is="slide.component"></component>
	</div>
</div>

</template>

<script>

jQuery.expr.filters.offscreen = function(el) {
	  var rect = el.getBoundingClientRect();
	  return (
	           (rect.x + rect.width) < 0 
	             || (rect.y + rect.height) < 0
	             || (rect.x > window.innerWidth || rect.y > window.innerHeight)
	         );
	};
	
window.checkOverflow = function (el)
	{
	   var curOverflow = el.style.overflow;

	   if ( !curOverflow || curOverflow === "visible" )
	      el.style.overflow = "hidden";

	   var isOverflowing = el.clientWidth < el.scrollWidth 
	      || el.clientHeight < el.scrollHeight;

	   el.style.overflow = curOverflow;

	   return isOverflowing;
	}


// import VueScrollSnap from 'vue-scroll-snap'

import Uploader from './Uploader'
import Projects from './Projects'

import VueScrollSnap from './VueScrollSnap'
import VueScrollactive from 'vue-scrollactive'

import {TweenMax} from "gsap/TweenMax";

Vue.use(VueScrollactive)



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
		    }
		},
		computed: {
			overflowRatio() {
				return this.$refs.list.scrollWidth / this.$refs.list.offsetWidth;
			},
			itemWidth() {
				return this.$refs.list.scrollWidth / this.slides.length;
			},
// 			selectedContent() {
// 				if (this.selected) {
// 					return this.emojis[this.slides.indexOf(this.selected)];
// 				}
// 				return "";
// 			},
			count() {
				return this.slides.length;
			},
			
			fuck: function() {
// 				var result = false;
// 				let container = $('.scroll-snap-container');
// 				let container_width = container.parent().width();
// 				container.find('.item').each(function(i) {
// 					if ((container_width / 2) < Math.abs($(this).position().left)) {
// 						result = $(this);
// 					}
// 				});
// 				return result;
// 				$('#fuck1').parent().width();
				return Math.abs($('#fuck1').position().left);
// 				return $('#fuck1').is(':visible');
			},
		},

		
		components: {
			Uploader,
			Projects,
			VueScrollSnap
		},
		
		beforeMount() {
			//
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
            //
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
        		  }
        	},
        	onTap: function() {
        		console.log('tap');
        	},
            handleScroll () {
            	console.log('swoop');
            },
    	}
	}
</script>

<style scoped>
  .item {
    /* Set the minimum height of the items to be the same as the height of the scroll-snap-container.*/
    min-width: 100%;
/*     flex: 0 0 100%; */
  }
 
  .scroll-snap-container {
    height: 250px;
/*     width: 500px; */
    flex: 0 0 100%;
  }
  
  .slider__list {
  		display: flex;
 		width: 100%;
 		height: 100%;
 		min-height: 100%;
		overflow: hidden;
  		transform: translateX(calc(var(--x, 0) * 1%));
  		padding-left: 0px;
  }
  
  .slider__item {
  		position: relative;
  		flex: 0 0 100%;
  		display: flex;
		justify-content: center;
		align-items: center;
		height: 100%;
/* 		margin-right: 12px; */
/* 		padding: 6px; */
  }
/*  
.slider {
	width: 100%;
	height: 120px;
	overflow: visible;
  position: relative;
  white-space: nowrap;

	&__list {
		display: flex;
		width: 100%;
		height: 100%;
		
		font-size: 2rem;
		backface-visibility: hidden;
		transform: translateX(calc(var(--x, 0) * 1%));
	}
	
	&__item {
		position: relative;
		flex: 0 0 140px;
		
		display: flex;
		justify-content: center;
		align-items: center;
		height: 100%;
		margin-right: 12px;
		padding: 6px;
		box-sizing: border-box;
		
		border-radius: 8px;
		text-align: center;
  	transition: opacity 0.15s ease;
		color: #fff;

		&:focus {
			opacity: 0.8;
		}
	}
}
*/
</style>