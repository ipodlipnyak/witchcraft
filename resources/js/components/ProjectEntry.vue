<template>
    <div class="project-entry">
    
        <b-card no-body class="overflow-hidden mb-2">
            <video 
                        v-if="project.status == 4" 
                        :id="'player-' + project.id" 
                        width="100%" 
                        controls>
                <source :src="'/storage/media/' + project.output.id" type="video/mp4" />
            </video>
            <b-card-img v-else v-on:click="selectProject()" :src="'/storage/thumbs/' + project.output.id" class="rounded-0"></b-card-img>
            <b-card-body>
                <b-card-text>
                    <p>{{ project.output.label ? project.output.label : project.output.name }}</p>
                </b-card-text>
            </b-card-body>
            <b-button v-if="project.status == 1" block squared @click="startProject()" variant="info">Start</b-button>
            <b-progress height="2rem" v-else-if="project.status == 2 || project.status == 4" :value="project.progress" :max="100" show-progress animated></b-progress>
            <b-button v-if="project.status == 4" block squared @click="downloadProject()" variant="success">Download</b-button>
        </b-card>

    </div>
</template>


<script>
import { CardPlugin, ButtonPlugin, ProgressPlugin } from 'bootstrap-vue';

Vue.use(ProgressPlugin)
Vue.use(CardPlugin)
Vue.use(ButtonPlugin)


export default {
	props: ['project'],
	data: function () {
		return {
			projects: [],
			player: '',
		}
	},
	
	mounted() {
		//		
	},
	
	watch: {
		//
	},
	
	methods: {
		selectProject: function() {
			this.$emit('select-project', 1);
		},
		startProject: function() {
			//
		},
		downloadProject: function() {
			//
		},
	},
}

</script>

<style lang='scss' scoped>
.project-entry {
/* 	height: 50px; */
}
.project-img {
 	width: 100%; 
}
.project-video {
/* 	height: 200px; */
}

.card {
	border-radius: 0;
	border: none;
}
.progress {
	border-radius: 0;
}
</style>