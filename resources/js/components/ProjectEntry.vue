<template>
    <div class="project-entry">
    
        <b-card no-body class="overflow-hidden mb-2">
            <video
                        v-if="projectEntry.status == 4"
                        preload="auto" 
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
            <b-button v-if="projectEntry.status == 1" block squared @click="startProject()" variant="info">Start</b-button>
            <b-button v-if="projectEntry.status == 2" block squared @click="stopProject()" variant="danger">Stop</b-button>
            <b-progress height="2rem" v-else-if="projectEntry.status == 3" :value="projectEntry.progress" :max="100" show-progress animated></b-progress>
            <b-button v-if="projectEntry.status == 4" block squared @click="downloadProject()" variant="success">Download</b-button>
        </b-card>

    </div>
</template>


<script>
import { CardPlugin, ButtonPlugin, ProgressPlugin } from 'bootstrap-vue';

Vue.use(ProgressPlugin)
Vue.use(CardPlugin)
Vue.use(ButtonPlugin)

export default {
	props: ['project', 'apiToken'],
	
	components: {
		//
	},
	
	data: function () {
		return {
			projectEntry: '',
			player: '',
		}
	},
	
	computed: {
		//
	},
	
	mounted() {
		this.projectEntry = this.project;
	},
	
	watch: {
		//
	},
	
	methods: {
		selectProject: function() {
			this.$emit('select-project');
		},
		refreshProjectData: function() {
			self = this;
			
    		axios.get('/api/projects/' + self.project.id + '/?api_token=' + self.apiToken)
    		.then(function (response) {
    			if (response.data.status == 'success') {
    				self.projectEntry = response.data.project;
    			}
    		})
    		.catch(function (error) {
    			console.log(error);
    		});
		},
		startProject: function() {
			self = this;
			
    		axios.post('/api/projects/' + self.project.id + '/start?api_token=' + self.apiToken, {
    			inputs: self.inputOrder
    		})
    		.then(function (response) {
    			if (response.data.status == 'success') {
    				self.refreshProjectData();
    			}
    		})
    		.catch(function (error) {
    			console.log(error);
    		});
		},
		stopProject: function() {
			self = this;
			
    		axios.post('/api/projects/' + self.project.id + '/stop?api_token=' + self.apiToken, {
    			inputs: self.inputOrder
    		})
    		.then(function (response) {
    			if (response.data.status == 'success') {
    				self.refreshProjectData();
    			}
    		})
    		.catch(function (error) {
    			console.log(error);
    		});
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

img {
	cursor: pointer;
}
</style>