<template>
<div class="container"><div class="row justify-content-center"><div class="col-md-8">

<!-- Edit selected project -->
<div v-if="projectSelected">
	<b-alert v-if="errorMessage" show variant="danger">{{ errorMessage }}</b-alert>
	
	<b-button-group  size="bg" class="btn-block">
		<b-button squared @click="updateProject" variant="success" class="mb-2">Save</b-button>
		<b-button squared @click="selectProject('')" variant="primary" class="mb-2">Close</b-button>
    </b-button-group>
</div>

<!-- New project -->
<div v-else-if="projectSelected === 0">
	<b-button-group  size="bg" class="btn-block">
		<b-button squared @click="saveProject" variant="success" class="mb-2">Save</b-button>
		<b-button squared @click="selectProject('')" variant="primary" class="mb-2">Close</b-button>
    </b-button-group>
</div>

<div v-if="projectSelected || projectSelected === 0">
	<b-input-group size="md" prepend="Name">
    	<b-form-input v-model="outputLabel"></b-form-input>
		<b-input-group-append>
			<b-button v-for="ext in fileExtensionsList"
				:key="ext"
				@click="outputFileExtension = ext" 
				:variant="outputFileExtension == ext ? 'primary' : 'outline-primary'">
				.{{ ext }}
			</b-button>
		</b-input-group-append>
	</b-input-group>
	
	<b-input-group size="md" prepend="Fade duration">
    	<b-form-input type="number" min="0" step="0.5" v-model="fadeDuration"></b-form-input>
	</b-input-group>
	
	<b-input-group size="md" prepend="Ratio">
		<b-form-input v-model="aspectWidth"></b-form-input>
		<b-form-input v-model="aspectHeight"></b-form-input>
		<template v-slot:append>
			<b-dropdown text="Presets" variant="primary">
				
				<b-dropdown-group v-for="standart in aspectPresets" :key="standart.standart" :header="standart.label">
					<b-dropdown-item v-for="set in standart.presets" 
						:key="set.name" 
						@click="selectPreset(set)">
						{{ set.name }}
					</b-dropdown-item>
				</b-dropdown-group>
				
			</b-dropdown>
    	</template>
	</b-input-group>
	
	<project-inputs ref="inputs" 
		class="pt-2"
		:api-token="apiToken" 
		:project-id="projectSelected.id">
	</project-inputs>
</div>

<!-- Projects listing -->
<div v-else>
	<div>
		<b-button block squared @click="selectProject(0)" variant="primary" class="mb-2">New project</b-button>
	</div>
	<!-- 
	<div v-for="(project, index) in projects" :key="project.id">
		<b-button block squared @click="selectProject(project.id)" variant="primary">{{ project.output.label }}</b-button>
	</div>
	 -->
	
	<simplebar class="bar" data-simplebar-auto-hide="false">
	<masonry
  		cols="2"
  		gutter="10">
  		<project-entry 
  			ref="entries"
  			v-for="project in projects" 
  			v-on:select-project="selectProject(project.id)"
  			v-on:refresh-projects-listing="getProjects"
  			:project="project" 
  			:key="project.id"
  			:api-token="apiToken">
  		</project-entry>
	</masonry>
	</simplebar>
	
</div>


</div></div></div>
</template>

<script>
import ProjectInputs from './ProjectInputs';
import ProjectEntry from './ProjectEntry';
import { AlertPlugin, InputGroupPlugin, FormInputPlugin, ButtonPlugin, ListGroupPlugin, CardPlugin, DropdownPlugin } from 'bootstrap-vue';

import simplebar from 'simplebar-vue';
import 'simplebar/dist/simplebar.min.css';

import VueMasonry from 'vue-masonry-css'
Vue.use(VueMasonry)

Vue.use(AlertPlugin)
Vue.use(InputGroupPlugin)
Vue.use(FormInputPlugin)
Vue.use(ButtonPlugin)
Vue.use(ListGroupPlugin)
Vue.use(CardPlugin)
Vue.use(DropdownPlugin)

export default {
	props: ['apiToken'],
	data: function () {
		return {
			projects: [],
			
			projectSelected: '',
			outputLabel: '',
			outputFileExtension: '',
			fadeDuration: 0,
			
			fileExtensionsList: ['mp4', 'mkv', 'ogg'],
			
			aspectWidth: 0,
			aspectHeight: 0,
			
			aspectPresets: [
				{
					standart: '16:9',
					r: '1.77',
					label: '16:9 R:1.77',
					presets: [
						{
							name: 'Full HD',
							w: 1920,
							h: 1080,
						},
						{
							name: 'HD',
							w: 1280,
							h: 720,
						},
						{
							name: 'WVGA',
							w: 848,
							h: 480,
						},
					],
				},
				{
					standart: '4:3',
					r: '1.33',
					label: '4:3 R:1.33',
					presets: [
						{
							name: 'UXGA',
							w: 1600,
							h: 1200,
						},
						{
							name: 'XGA',
							w: 1024,
							h: 768,
						},
						{
							name: 'SVGA',
							w: 800,
							h: 600,
						},
					],
				},
				{
					standart: '1:1',
					r: '1',
					label: '1:1 R:1',
					presets: [
						{
							name: 'Square',
							w: 800,
							h: 800,
						},
					],
				},
			],
			
			errorMessage: '',
			
		}
	},
	
	computed: {
		defaultAspectPreset() {
			return {
				w: 848,
				h: 480,
			}
		},
		
		defaultExtension() {
			return 'mp4';
		},
		
		defaultOutputName() {
			return 'output';
		},
		
		defaultFadeDuration() {
			return 0;
		},
		
		outputName() {
			return this.outputLabel && this.outputFileExtension ? this.outputLabel + '.' + this.outputFileExtension : '';
		},
	},
		
	components: {
		ProjectInputs,
		ProjectEntry,
		simplebar,
	},
		
	mounted() {
		this.getProjects();
		this.initProject();
	},
	
	watch: {
		projectSelected: function (newVal, oldVal) {
			this.initProject();
		}
	},
	
	methods: {
		initProject: function() {
			let self = this;
			self.errorMessage = '';
			
			self.outputLabel = self.projectSelected.output ? self.projectSelected.output.label : self.defaultOutputName;
			self.outputFileExtension = self.projectSelected.output ? self.projectSelected.output.name.split('.').pop() : self.defaultExtension;
			self.fadeDuration = self.projectSelected.concat_fade_duration ? self.projectSelected.concat_fade_duration : self.defaultFadeDuration;
			
			if(self.projectSelected.output && self.projectSelected.output.width && self.projectSelected.output.height) {
				self.aspectWidth = self.projectSelected.output.width;
				self.aspectHeight = self.projectSelected.output.height;
			} else {
				self.aspectWidth = self.defaultAspectPreset.w;
				self.aspectHeight = self.defaultAspectPreset.h;
			}
		},
		
		saveProject: function() {
			self = this;
			
			axios.post('/api/projects?api_token=' + this.apiToken, {
				label: self.outputLabel,
				extension: self.outputFileExtension,
				width: self.aspectWidth,
				height: self.aspectHeight,
				concat_fade_duration: self.fadeDuration,
			})
    		.then(function (response) {
    			if (response.data.status == 'success') {
    				self.$refs.inputs.saveInputs(response.data.id);
        			self.getProjects();
        			self.selectProject(response.data.id);
    			}
    		})
    		.catch(function (error) {
    			console.log(error);
    		});
		},
		
		updateProject: function() {
			self = this;
			self.$refs.inputs.saveInputs();
			
			axios.post('/api/projects/' + self.projectSelected.id + '?api_token=' + this.apiToken, {
				label: self.outputLabel,
				extension: self.outputFileExtension,
				width: self.aspectWidth,
				height: self.aspectHeight,
				concat_fade_duration: self.fadeDuration,
			})
    		.then(function (response) {
    			if (response.data.status == 'error') {
    				self.errorMessage = response.data.message ? response.data.message : '';
    			} else {
    				self.errorMessage = '';
    				self.getProjects();
    			}
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
    	
    	selectPreset: function(set) {
    		let inputs_list = this.$refs.inputs.inputs;
    		// check if first input in portrait mode
    		if (inputs_list.length > 0 && inputs_list[0].height > inputs_list[0].width) {
    			this.aspectHeight = set.w;
        		this.aspectWidth = set.h;
    		} else {
    			this.aspectHeight = set.h;
        		this.aspectWidth = set.w;
    		}
    	},
	},
}
</script>

<style scoped>
.bar {
	height: 80vh;
	overflow-x: hidden;
}
</style>
