<?php

function showFlashMessage()
{
	if(user()->hasFlash('message'))
	{
		echo '<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">';
		echo user()->getFlash('message');
		echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
		echo '</div>';
	}

	if(user()->hasFlash('error'))
	{
		echo '<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">';
		echo user()->getFlash('error');
		echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
		echo '</div>';
	}
}

function showPageContent($content)
{
	echo '<div class="container-fluid mt-4 mb-5">';
    echo '  <div class="row">';
    echo '    <div class="col-12">';

	showFlashMessage();
	echo $content;

	echo '    </div>';
    echo '  </div>';
	echo '</div>';
}




