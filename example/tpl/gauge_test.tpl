<?php

	# gauge generated test template
	
	# boilerplate code starts
		
		%3$s
			
	# boilerplate code ends
	
	$this->start('%1$s');

	for ($__=0; $__<%4$d; ++$__){
	
		# benchmark code starts
		
		%2$s
			
		# benchmark code ends
	
	}

	$this->stop();
	
	$this->write('%1$s');