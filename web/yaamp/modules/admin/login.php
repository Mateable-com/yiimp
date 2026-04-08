<?php
/* @var $this AdminController */
/* @var $model LoginForm */
/* @var $loginform CActiveForm  */

$this->pageTitle = 'Admin Login - ' . YAAMP_SITE_NAME;
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-lg border-0 rounded-3 mt-5">
                <div class="card-header bg-dark text-white text-center py-4 border-0">
                    <h3 class="mb-0 fw-bold"><i class="fa fa-user-shield me-2 text-primary"></i>Admin Portal</h3>
                </div>
                <div class="card-body p-5">
                    <p class="text-muted text-center mb-4">Secure access for pool administrators</p>

                    <?php $loginform = $this->beginWidget('CActiveForm', array(
                        'id' => 'login-form',
                        'enableClientValidation' => true,
                        'clientOptions' => array(
                            'validateOnSubmit' => true,
                        ),
                        'htmlOptions' => array('class' => 'needs-validation')
                    )); ?>

                    <div class="mb-3">
                        <?php echo $loginform->labelEx($model, 'username', array('class' => 'form-label fw-bold small text-uppercase text-muted')); ?>
                        <?php echo $loginform->textField($model, 'username', array('class' => 'form-control form-control-lg border-2', 'placeholder' => 'Enter username')); ?>
                        <?php echo $loginform->error($model, 'username', array('class' => 'text-danger small mt-1')); ?>
                    </div>

                    <div class="mb-4">
                        <?php echo $loginform->labelEx($model, 'password', array('class' => 'form-label fw-bold small text-uppercase text-muted')); ?>
                        <?php echo $loginform->passwordField($model, 'password', array('class' => 'form-control form-control-lg border-2', 'placeholder' => '••••••••')); ?>
                        <?php echo $loginform->error($model, 'password', array('class' => 'text-danger small mt-1')); ?>
                    </div>

                    <div class="mb-4 form-check">
                        <?php echo $loginform->checkBox($model, 'rememberMe', array('class' => 'form-check-input')); ?>
                        <?php echo $loginform->label($model, 'rememberMe', array('class' => 'form-check-label small')); ?>
                    </div>

                    <div class="d-grid gap-2">
                        <?php echo CHtml::submitButton('Login to Dashboard', array('class' => 'btn btn-primary btn-lg fw-bold shadow-sm py-3')); ?>
                    </div>

                    <?php $this->endWidget(); ?>
                </div>
                <div class="card-footer bg-light border-0 py-3 text-center">
                    <a href="/" class="text-decoration-none small text-muted"><i class="fa fa-arrow-left me-1"></i> Back to Pool Home</a>
                </div>
            </div>
            
            <div class="text-center mt-4 text-muted small opacity-75">
                &copy; <?php echo date('Y'); ?> <?php echo YAAMP_SITE_NAME; ?> Security System
            </div>
        </div>
    </div>
</div>

<style>
    body {
        background: #f4f7f6;
    }
    .form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    }
    .btn-primary {
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3) !important;
    }
</style>
