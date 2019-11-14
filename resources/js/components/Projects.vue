<template>
<div class="container"><div class="row justify-content-center"><div class="col-md-8">

<div v-if="projectSelected || projectSelected === 0">
	<b-button block squared @click="selectProject('')" variant="primary">Close</b-button>
	
	<b-input-group size="lg" prepend="Output file name">
    	<b-form-input>
    	</b-form-input>
	</b-input-group>
	
	<project-inputs ref="inputs" 
		v-if="projectSelected" 
		:api-token="apiToken" 
		:project-id="projectSelected.id">
	</project-inputs>
</div>

<div v-else>
	<div>
		<b-button block squared @click="selectProject(0)" variant="primary">New project</b-button>
	</div>
	<div v-for="(project, index) in projects" :key="project.id">
		<b-button block squared @click="selectProject(project.id)" variant="primary">{{ project.output.name_to_display }}</b-button>
	</div>
</div>




</div></div></div>
</template>

<script>
import ProjectInputs from './ProjectInputs';
import { InputGroupPlugin } from 'bootstrap-vue';

Vue.use(InputGroupPlugin)

export default {
	props: ['apiToken'],
	data: function () {
		return {
			filesUploaded: [],
			projects: [],
			
			projectSelected: '',
			}
		},
		
	components: {
		ProjectInputs,
	},
		
	mounted() {
		this.getFiles();
		this.getProjects();
	},
	
	methods: {
		getFiles: function() {
    		self = this;
    		axios.get('/api/files?api_token=' + this.apiToken)
    		.then(function (response) {
    			self.filesUploaded = response.data;
    		})
    		.catch(function (error) {
    			console.log(error);
    		});
    	},
    	
    	getProjects: function() {
    		self = this;
    		axios.get('/api/projects?api_token=' + this.apiToken)
    		.then(function (response) {
    			self.projects = response.data;
    		})
    		.catch(function (error) {
    			console.log(error);
    		});
    	},
    	
    	selectProject: function(id) {
    		self = this;
    		
    		if (id > 0) {
    			this.$emit('lock-swipe');
        		axios.get('/api/projects/' + id + '?api_token=' + this.apiToken)
        		.then(function (response) {
        			if (response.data.status == 'success') {
        				self.projectSelected = response.data.project;
        			}
        		})
        		.catch(function (error) {
        			console.log(error);
        		});
    		} else if (id === 0) {
    			this.$emit('lock-swipe');
    			self.projectSelected = id;
    		} else {
    			this.$emit('unlock-swipe');
    			self.projectSelected = id;
    		}
    	},
	},
}
</script>

<style scoped>
</style>