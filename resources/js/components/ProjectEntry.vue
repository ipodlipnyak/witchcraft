<template>
    <div class="project-entry">
    
        <b-card no-body class="overflow-hidden mb-2">
        	<fill-up-button ref="deleteButton" v-on:do-it="deleteProject">Hold to delete</fill-up-button>
            <video
                        v-if="projectEntry.status == 4"
                        preload="none" 
                        :poster="'/storage/thumbs/' + project.output.id + '/original'"
                        :id="'player-' + project.id" 
                        width="100%" 
                        controls>
                <source :src="'/storage/media/' + project.output.id" :type="projectEntry.mime_type" />
            </video>
            <b-card-img 
            	v-else 
            	v-on:click="selectProject()" 
            	:src="'/storage/thumbs/' + project.output.id + '/original'"
            	:class="[selectable ? 'clickable' : '']" 
            	class="rounded-0">
            </b-card-img>
            <b-card-body>
                <b-card-text>
                    <p>{{ project.output.label ? project.output.label : project.output.name }}</p>
                </b-card-text>
            </b-card-body>
            <b-button v-if="projectEntry.status == 1" block squared @click="startProject()" variant="info">Start</b-button>
            <b-button v-if="projectEntry.status == 2" block squared @click="stopProject()" variant="danger">Stop</b-button>
            <b-progress height="2rem" v-else-if="projectEntry.status == 3" :value="projectEntry.progress" :max="100" show-progress animated></b-progress>
            <b-button v-if="projectEntry.status == 4" 
            	block 
            	squared 
            	:href="'/storage/media/' + project.output.id" 
            	:download="download_name" 
            	variant="success">
            	Download
            </b-button>
            <b-button v-if="projectEntry.status == 5" block squared @click="selectProject()" variant="danger">Encoding failed</b-button>
		
		<!-- 
            <b-alert v-if="projectEntry.status == 5" show variant="danger">
            <p>Encoding failed</p>
            <hr> 
            <small>Edit project,</small>
            <small>fix possible unconsistency in inputs</small>
            <small>and try start it again</small>
            </b-alert>
		-->
            
        </b-card>

    </div>
</template>


<script>
import FillUpButton from './FillUpButton'
import {CardPlugin, ButtonPlugin, ProgressPlugin } from 'bootstrap-vue';

Vue.use(ProgressPlugin)
Vue.use(CardPlugin)
Vue.use(ButtonPlugin)

export default {
	props: ['project', 'apiToken'],
	
	components: {
		FillUpButton
	},
	
	data: function () {
		return {
			projectEntry: '',
			player: '',
		}
	},
	
	computed: {
		selectable() {
			if (this.projectEntry && this.projectEntry.status) {
				return  [1, 5].includes(this.projectEntry.status);
			}
			
			return false;
			
		},
		download_name() {
			return this.projectEntry.output.label + '.' + this.projectEntry.file_extension
		},
	},
	
	mounted() {
		var self = this;
		
		this.projectEntry = this.project;
		this.refreshProjectData();
		
		// socket.io SUBSCRIBE to 'project.{id}' channel
		Echo.private(`project.${this.project.id}`)
			.listen('.App\\Events\\ProjectUpdate', (e) => {
				self.projectEntry.progress = e.project.progress;
				self.projectEntry.status = e.project.status;
	    });
	},
	
	watch: {
		//
	},
	
	methods: {
		deleteProject: function() {
			console.log('do it');
		},
		selectProject: function() {
			if (this.selectable) {
				this.$emit('select-project');
			}
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
/* 	transform: translateX(calc(var(--x, 0) * 1%)); */
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