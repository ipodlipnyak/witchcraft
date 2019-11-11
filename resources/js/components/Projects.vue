<template>
<div class="container"><div class="row justify-content-center"><div class="col-md-8">

<div v-if="projectSelected || projectSelected === 0">
	<b-button block squared @click="selectProject('')" variant="primary">Close</b-button>
	{{ projectSelected }}
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
export default {
	props: ['apiToken'],
	data: function () {
		return {
			filesUploaded: [],
			projects: [],
			
			projectSelected: '',
			}
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
        		axios.get('/api/projects/' + id + '?api_token=' + this.apiToken)
        		.then(function (response) {
        			if (response.data.status == 'success') {
        				self.projectSelected = response.data.project;
        			}
        		})
        		.catch(function (error) {
        			console.log(error);
        		});
    		} else {
    			self.projectSelected = id;
    		}
    	},
	},
}
</script>

<style scoped>
</style>