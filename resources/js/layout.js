        document.querySelectorAll('.nav-link, .nav-submenu').forEach(function(link) {
            var href = link.getAttribute('href') || '';
            var path = href.replace(/^https?:\/\/[^/]+/, '').replace(/\/$/, '') || '/';
            var current = window.location.pathname.replace(/\/$/, '') || '/';
            if (path === current) link.classList.add('router-link-active');
        });

        var modalStoreKeys = {
            consultationCreateModal: 'consultation',
            printReferralConfirmModal: 'printReferral',
            outwardReferralShowModal: 'outward',
            diagnosisModal: 'diagnosis',
            prescriptionModal: 'prescription',
        };

        function getModalElements(modalId, panelId) {
            return {
                modal: document.getElementById(modalId),
                panel: document.getElementById(panelId),
            };
        }

        function showModal(modalId, panelId, callback) {
            var key = modalStoreKeys[modalId];
            var elements = getModalElements(modalId, panelId);
            if (!key || !elements.modal || !elements.panel) return false;
            window.Alpine.store('modals')[key] = true;
            document.body.classList.add('modal-open');
            requestAnimationFrame(function() {
                var focusable = elements.panel.querySelector('input, select, textarea, button, [tabindex]:not([tabindex="-1"])');
                var target = focusable || elements.panel;
                target.focus();
                if (typeof callback === 'function') {
                    callback();
                }
            });
            return true;
        }

        function hideModal(modalId, panelId, onHidden) {
            var key = modalStoreKeys[modalId];
            var elements = getModalElements(modalId, panelId);
            if (!key || !elements.modal || !elements.panel) return false;
            window.Alpine.store('modals')[key] = false;
            document.body.classList.remove('modal-open');
            if (typeof onHidden === 'function') {
                onHidden();
            }
            return true;
        }

        function initConsultationCreateModalForm() {
            cacheConsultationCreateModalHeader();
            initOutwardReferralPreviewSync();

            var modeSelect = document.getElementById('mode_of_transaction');
            var referredContainer = document.getElementById('referred_from_container');
            if (!modeSelect || !referredContainer) return;

            function toggleReferredFrom() {
                referredContainer.style.display = modeSelect.value === 'Referral' ? 'block' : 'none';
            }

            toggleReferredFrom();
            modeSelect.removeEventListener('change', modeSelect._consultationToggleHandler);
            modeSelect._consultationToggleHandler = toggleReferredFrom;
            modeSelect.addEventListener('change', toggleReferredFrom);
        }

        function openConsultationCreateModal(patientId) {
            var content = document.getElementById('consultationCreateModalContent');
            if (!content || !patientId) return;

            content.innerHTML = '<div class="p-8 text-center text-sm" style="color: var(--ink-muted);"><i class="fa-solid fa-spinner fa-spin mr-2" aria-hidden="true"></i>Loading consultation form…</div>';
            if (!showModal('consultationCreateModal', 'consultationCreateModalPanel')) {
                content.innerHTML = '<div class="p-6 text-center text-sm" style="color: #b91c1c;">Unable to open consultation modal.</div>';
                return;
            }

            fetch(window.BHCIS.routes.consultationsCreate.replace('__PID__', encodeURIComponent(patientId)), {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Failed to load consultation form: ' + response.status);
                }
                return response.text();
            })
            .then(function(html) {
                content.innerHTML = html;
                initConsultationCreateModalForm();
                resetConsultationCreateModalView();
            })
            .catch(function(error) {
                console.error('Consultation modal load failed:', error);
                content.innerHTML = '<div class="p-6 text-center text-sm" style="color: #b91c1c;">Unable to load the consultation form. Please try again.</div>';
            });
        }

        function closeConsultationCreateModal() {
            resetConsultationCreateModalView();
            hideModal('consultationCreateModal', 'consultationCreateModalPanel', function() {
                var content = document.getElementById('consultationCreateModalContent');
                if (content) {
                    content.innerHTML = '';
                }
            });
        }

        var outwardReferralWizardState = {
            currentStep: 1,
            totalSteps: 3,
            stepNames: ['Referral Details', 'Preview', 'Confirmation'],
        };

        function outwardReferralPreviewFieldMap() {
            return [
                { sourceId: 'outward_referred_to', previewId: 'outward_preview_referred_to' },
                { sourceId: 'outward_referral_reason_details', previewId: 'outward_preview_referral_reason_details' },
                { sourceId: 'outward_pertinent_history', previewId: 'outward_preview_pertinent_history' },
                { sourceId: 'outward_actions_taken', previewId: 'outward_preview_actions_taken' },
            ];
        }

        function updateOutwardReferralPreviewReasonsEmptyState() {
            var empty = document.getElementById('outward_preview_reasons_empty');
            var list = document.getElementById('outward_preview_reasons_list');
            var checked = document.querySelectorAll('#outwardReferralWizardStep2 input[data-preview-field="referral_reasons"]:checked');
            if (!empty || !list) return;
            empty.classList.toggle('hidden', checked.length > 0);
            list.classList.toggle('opacity-100', checked.length > 0);
        }

        function syncOutwardReferralPreviewFromStep1() {
            outwardReferralPreviewFieldMap().forEach(function(field) {
                var source = document.getElementById(field.sourceId);
                var preview = document.getElementById(field.previewId);
                if (source && preview) {
                    preview.value = source.value;
                }
            });

            document.querySelectorAll('#outwardReferralWizardStep1 input[name="referral_reasons[]"]').forEach(function(sourceCheckbox) {
                var previewCheckbox = document.querySelector(
                    '#outwardReferralWizardStep2 input[data-preview-field="referral_reasons"][value="' + sourceCheckbox.value + '"]'
                );
                if (previewCheckbox) {
                    previewCheckbox.checked = sourceCheckbox.checked;
                }
            });

            updateOutwardReferralPreviewReasonsEmptyState();
        }

        function syncOutwardReferralStep1FromPreview(previewEl) {
            if (!previewEl) return;

            if (previewEl.getAttribute('data-preview-field') === 'referral_reasons') {
                var sourceCheckbox = document.querySelector(
                    '#outwardReferralWizardStep1 input[name="referral_reasons[]"][value="' + previewEl.value + '"]'
                );
                if (sourceCheckbox) {
                    sourceCheckbox.checked = previewEl.checked;
                }
                updateOutwardReferralPreviewReasonsEmptyState();
                return;
            }

            var sourceId = previewEl.getAttribute('data-preview-source');
            var source = sourceId ? document.getElementById(sourceId) : null;
            if (source) {
                source.value = previewEl.value;
            }
        }

        function initOutwardReferralPreviewSync() {
            var wizardView = document.getElementById('consultationCreateOutwardWizardView') || document.getElementById('outwardReferralShowPanel');
            if (!wizardView) return;

            wizardView.querySelectorAll('[data-preview-source]').forEach(function(previewEl) {
                previewEl.removeEventListener('input', previewEl._outwardPreviewSyncHandler);
                previewEl.removeEventListener('change', previewEl._outwardPreviewSyncHandler);
                previewEl._outwardPreviewSyncHandler = function() {
                    syncOutwardReferralStep1FromPreview(previewEl);
                };
                previewEl.addEventListener('input', previewEl._outwardPreviewSyncHandler);
                previewEl.addEventListener('change', previewEl._outwardPreviewSyncHandler);
            });
        }

        function validateOutwardReferralStep1() {
            var referredTo = document.getElementById('outward_referred_to');
            var history = document.getElementById('outward_pertinent_history');
            var reasons = document.querySelectorAll('#outwardReferralWizardStep1 input[name="referral_reasons[]"]:checked');
            var message = '';

            if (!referredTo || !referredTo.value.trim()) {
                message = 'Please select or enter a destination facility.';
                referredTo && referredTo.focus();
            } else if (reasons.length === 0) {
                message = 'Please select at least one reason for referral.';
                var firstReason = document.querySelector('#outwardReferralWizardStep1 input[name="referral_reasons[]"]');
                firstReason && firstReason.focus();
            } else if (!history || !history.value.trim()) {
                message = 'Please enter the pertinent history of illness.';
                history && history.focus();
            }

            if (message && typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Incomplete referral details', text: message, confirmButtonColor: '#0d4a3c' });
            }

            return !message;
        }

        function validateConsultationVitalsForReferral() {
            var temperature = document.getElementById('temperature');
            var systolic = document.getElementById('bp_systolic');
            var diastolic = document.getElementById('bp_diastolic');
            var weight = document.getElementById('weight');
            var height = document.getElementById('height');
            var message = '';

            if (!temperature || !temperature.value.trim()) {
                message = 'Please enter temperature before creating an outward referral.';
                temperature && temperature.focus();
            } else if (!systolic || !systolic.value.trim()) {
                message = 'Please enter systolic blood pressure before creating an outward referral.';
                systolic && systolic.focus();
            } else if (!diastolic || !diastolic.value.trim()) {
                message = 'Please enter diastolic blood pressure before creating an outward referral.';
                diastolic && diastolic.focus();
            } else if (!weight || !weight.value.trim()) {
                message = 'Please enter weight before creating an outward referral.';
                weight && weight.focus();
            } else if (!height || !height.value.trim()) {
                message = 'Please enter height before creating an outward referral.';
                height && height.focus();
            }

            if (message && typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Complete vitals first', text: message, confirmButtonColor: '#0d4a3c' });
            }

            return !message;
        }

        function copyOutwardReferralDataToMainForm() {
            var referralFlag = document.getElementById('outward_refer_to_higher_facility');
            var hiddenReferredTo = document.getElementById('outward_hidden_referred_to');
            var hiddenReasonDetails = document.getElementById('outward_hidden_referral_reason_details');
            var hiddenPertinentHistory = document.getElementById('outward_hidden_pertinent_history');
            var hiddenActionsTaken = document.getElementById('outward_hidden_actions_taken');
            var referralReasonsContainer = document.getElementById('outward_hidden_referral_reasons');

            if (!referralFlag || !hiddenReferredTo || !hiddenReasonDetails || !hiddenPertinentHistory || !hiddenActionsTaken || !referralReasonsContainer) {
                return;
            }

            referralFlag.value = '1';
            hiddenReferredTo.value = document.getElementById('outward_referred_to')?.value.trim() || '';
            hiddenReasonDetails.value = document.getElementById('outward_referral_reason_details')?.value.trim() || '';
            hiddenPertinentHistory.value = document.getElementById('outward_pertinent_history')?.value.trim() || '';
            hiddenActionsTaken.value = document.getElementById('outward_actions_taken')?.value.trim() || '';

            referralReasonsContainer.innerHTML = '';
            document.querySelectorAll('#outwardReferralWizardStep1 input[name="referral_reasons[]"]:checked').forEach(function(sourceCheckbox) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'referral_reasons[]';
                input.value = sourceCheckbox.value;
                referralReasonsContainer.appendChild(input);
            });
        }

        function populateOutwardReferralConfirmationStep() {
            document.querySelectorAll('#outwardReferralWizardStep2 [data-preview-source]').forEach(function(previewEl) {
                syncOutwardReferralStep1FromPreview(previewEl);
            });

            var patientNameEl = document.getElementById('outward_confirm_patient_name');
            var patientMetaEl = document.getElementById('outward_confirm_patient_meta');
            var contextEl = document.getElementById('consultationReferralContext');

            if (contextEl) {
                var patientName = contextEl.dataset.patientName?.trim() || '—';
                var patientMeta = contextEl.dataset.patientMeta?.trim() || '—';
                if (patientNameEl) {
                    patientNameEl.textContent = patientName;
                }
                if (patientMetaEl) {
                    patientMetaEl.textContent = patientMeta;
                }

                var contextUrl = contextEl.dataset.referralContextUrl;
                if (contextUrl && window.fetch) {
                    fetch(contextUrl, { headers: { 'Accept': 'application/json' } })
                        .then(function(response) {
                            if (!response.ok) {
                                throw new Error('Referral context request failed.');
                            }
                            return response.json();
                        })
                        .then(function(data) {
                            if (patientNameEl && data.patient_name) {
                                patientNameEl.textContent = data.patient_name;
                            }
                            if (patientMetaEl && data.patient_meta) {
                                patientMetaEl.textContent = data.patient_meta;
                            }
                            var confirmVitals = document.getElementById('outward_confirm_vitals');
                            if (confirmVitals && data.vitals_summary) {
                                confirmVitals.textContent = data.vitals_summary;
                            }
                        })
                        .catch(function() {
                            // silent fallback to existing page data
                        });
                }
            } else {
                var subtitle = document.getElementById('consultationCreateModalSubtitle');
                var meta = document.getElementById('consultationCreateModalMeta');

                if (patientNameEl && subtitle) {
                    patientNameEl.textContent = subtitle.textContent.replace(/^Attending to\s*/i, '').trim() || '—';
                }
                if (patientMetaEl && meta) {
                    patientMetaEl.textContent = meta.textContent.trim() || '—';
                }
            }

            var referredTo = document.getElementById('outward_referred_to');
            var confirmReferredTo = document.getElementById('outward_confirm_referred_to');
            if (confirmReferredTo) {
                confirmReferredTo.textContent = referredTo?.value.trim() || '—';
            }

            var reasonsList = document.getElementById('outward_confirm_reasons');
            if (reasonsList) {
                reasonsList.innerHTML = '';
                document.querySelectorAll('#outwardReferralWizardStep1 input[name="referral_reasons[]"]:checked').forEach(function(checkbox) {
                    var label = checkbox.closest('label')?.querySelector('span')?.textContent?.trim() || checkbox.value;
                    var item = document.createElement('li');
                    item.textContent = label;
                    reasonsList.appendChild(item);
                });
                if (!reasonsList.children.length) {
                    var emptyItem = document.createElement('li');
                    emptyItem.textContent = 'No reasons selected';
                    emptyItem.style.color = 'var(--ink-subtle)';
                    reasonsList.appendChild(emptyItem);
                }
            }

            var reasonDetails = document.getElementById('outward_referral_reason_details');
            var confirmReasonDetails = document.getElementById('outward_confirm_reason_details');
            if (confirmReasonDetails) {
                confirmReasonDetails.textContent = reasonDetails?.value.trim() || 'No additional details provided.';
            }

            var history = document.getElementById('outward_pertinent_history');
            var confirmHistory = document.getElementById('outward_confirm_pertinent_history');
            if (confirmHistory) {
                confirmHistory.textContent = history?.value.trim() || '—';
            }

            var actions = document.getElementById('outward_actions_taken');
            var confirmActions = document.getElementById('outward_confirm_actions_taken');
            if (confirmActions) {
                confirmActions.textContent = actions?.value.trim() || 'No actions recorded.';
            }

            var confirmVitals = document.getElementById('outward_confirm_vitals');
            if (confirmVitals) {
                var temp = document.getElementById('temperature')?.value || '—';
                var sys = document.getElementById('bp_systolic')?.value || '—';
                var dia = document.getElementById('bp_diastolic')?.value || '—';
                var weight = document.getElementById('weight')?.value || '—';
                var height = document.getElementById('height')?.value || '—';
                confirmVitals.textContent = 'BP ' + sys + '/' + dia + ' mmHg · Temp ' + temp + '°C · Weight ' + weight + ' kg · Height ' + height + ' cm';
            }
        }

        function confirmOutwardReferralAndSubmit() { 
            document.querySelectorAll('#outwardReferralWizardStep2 [data-preview-source]').forEach(function(previewEl) {
                syncOutwardReferralStep1FromPreview(previewEl);
            });
            copyOutwardReferralDataToMainForm();

            var intakeForm = document.querySelector('#consultationCreateIntakeView form') || document.querySelector('#finalizeForm') || document.querySelector('#consultationShowReferralForm');
            if (!intakeForm) {
                var message = 'Referral submission failed because the referral form was not found. Please refresh the page and try again.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Referral failed', text: message, confirmButtonColor: '#0d4a3c' });
                } else {
                    alert(message);
                }
                return;
            }

            intakeForm.submit();
        }


        var consultationCreateModalView = 'intake';

        function cacheConsultationCreateModalHeader() {
            var subtitle = document.getElementById('consultationCreateModalSubtitle');
            if (subtitle && !subtitle.dataset.initialHtml) {
                subtitle.dataset.initialHtml = subtitle.innerHTML;
            }
        }

        function consultationCreateModalSetView(view) {
            var intakeView = document.getElementById('consultationCreateIntakeView');
            var wizardView = document.getElementById('consultationCreateOutwardWizardView');
            var title = document.getElementById('consultationCreateModalTitle');
            var subtitle = document.getElementById('consultationCreateModalSubtitle');
            var meta = document.getElementById('consultationCreateModalMeta');

            if (!intakeView || !wizardView) return;

            consultationCreateModalView = view;

            if (view === 'wizard') {
                intakeView.classList.add('hidden');
                intakeView.setAttribute('aria-hidden', 'true');
                wizardView.classList.remove('hidden');
                wizardView.setAttribute('aria-hidden', 'false');

                if (title) title.textContent = 'Outward Referral';
                if (subtitle) subtitle.textContent = 'Refer patient to a higher-level facility';
                if (meta) meta.classList.add('hidden');
                return;
            }

            wizardView.classList.add('hidden');
            wizardView.setAttribute('aria-hidden', 'true');
            intakeView.classList.remove('hidden');
            intakeView.setAttribute('aria-hidden', 'false');

            if (title) title.textContent = 'New Consultation';
            if (subtitle && subtitle.dataset.initialHtml) subtitle.innerHTML = subtitle.dataset.initialHtml;
            if (meta) meta.classList.remove('hidden');
        }

        function resetConsultationCreateModalView() {
            outwardReferralWizardState.currentStep = 1;
            consultationCreateModalSetView('intake');
            outwardReferralWizardUpdateUi();
        }

        function outwardReferralWizardUpdateUi() {
            var state = outwardReferralWizardState;
            var stepLabel = document.getElementById('outwardReferralWizardStepLabel');
            var stepName = document.getElementById('outwardReferralWizardStepName');
            var progressBar = document.getElementById('outwardReferralWizardProgressBar');
            var nextBtn = document.getElementById('outwardReferralWizardNextBtn');

            if (stepLabel) {
                stepLabel.textContent = 'Step ' + state.currentStep + ' of ' + state.totalSteps;
            }
            if (stepName) {
                stepName.textContent = state.stepNames[state.currentStep - 1] || '';
            }
            if (progressBar) {
                progressBar.style.width = ((state.currentStep / state.totalSteps) * 100) + '%';
            }

            document.querySelectorAll('.outward-referral-wizard-step').forEach(function(stepEl) {
                var stepNumber = parseInt(stepEl.getAttribute('data-wizard-step'), 10);
                var isActive = stepNumber === state.currentStep;
                stepEl.classList.toggle('hidden', !isActive);
                stepEl.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            });

            if (nextBtn) {
                if (state.currentStep >= state.totalSteps) {
                    nextBtn.textContent = 'Confirm & save referral';
                    nextBtn.setAttribute('aria-label', 'Confirm and save referral');
                } else if (state.currentStep === 2) {
                    nextBtn.textContent = 'Continue to confirmation';
                } else {
                    nextBtn.textContent = 'Next';
                }
            }
        }

        function outwardReferralWizardGoNext() {
            if (outwardReferralWizardState.currentStep === 1) {
                if (!validateOutwardReferralStep1()) {
                    return;
                }
                syncOutwardReferralPreviewFromStep1();
            }

            if (outwardReferralWizardState.currentStep === 2) {
                document.querySelectorAll('#outwardReferralWizardStep2 [data-preview-source]').forEach(function(previewEl) {
                    syncOutwardReferralStep1FromPreview(previewEl);
                });
                populateOutwardReferralConfirmationStep();
            }

            if (outwardReferralWizardState.currentStep >= outwardReferralWizardState.totalSteps) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'question',
                        title: 'Confirm outward referral?',
                        text: 'This will save the consultation and create the outward referral record.',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, save referral',
                        cancelButtonText: 'Review again',
                        confirmButtonColor: '#0d4a3c',
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            confirmOutwardReferralAndSubmit();
                        }
                    });
                } else {
                    confirmOutwardReferralAndSubmit();
                }
                return;
            }

            outwardReferralWizardState.currentStep += 1;
            outwardReferralWizardUpdateUi();

            if (outwardReferralWizardState.currentStep === 2) {
                syncOutwardReferralPreviewFromStep1();
            }

            if (outwardReferralWizardState.currentStep === 3) {
                populateOutwardReferralConfirmationStep();
            }
        }

        function outwardReferralWizardGoBack() {
            if (outwardReferralWizardState.currentStep <= 1) {
                closeOutwardReferralWizard();
                return;
            }

            document.querySelectorAll('#outwardReferralWizardStep2 [data-preview-source]').forEach(function(previewEl) {
                syncOutwardReferralStep1FromPreview(previewEl);
            });

            outwardReferralWizardState.currentStep -= 1;
            outwardReferralWizardUpdateUi();
        }

        function openOutwardReferralWizard() {
            if (!validateConsultationVitalsForReferral()) {
                return;
            }

            var wizardView = document.getElementById('consultationCreateOutwardWizardView');
            if (!wizardView) return;

            cacheConsultationCreateModalHeader();
            outwardReferralWizardState.currentStep = 1;
            outwardReferralWizardUpdateUi();
            consultationCreateModalSetView('wizard');
        }

        function openConsultationOutwardReferralWizard() {
            var opened = showModal('outwardReferralShowModal', 'outwardReferralShowPanel');
            if (!opened) return;
            outwardReferralWizardState.currentStep = 1;
            outwardReferralWizardUpdateUi();
            initOutwardReferralPreviewSync();
        }

        function closeOutwardReferralWizard(resetReferralFlag = true) {
            if (consultationCreateModalView === 'wizard') {
                var referralFlag = document.getElementById('outward_refer_to_higher_facility');
                if (referralFlag && resetReferralFlag) {
                    referralFlag.value = '0';
                }
                consultationCreateModalSetView('intake');
                return;
            }

            var referralFlag = document.getElementById('outward_refer_to_higher_facility');
            if (referralFlag && resetReferralFlag) {
                referralFlag.value = '0';
            }
            hideModal('outwardReferralShowModal', 'outwardReferralShowPanel');
        }

        function closeConsultationOutwardReferralWizard() {
            hideModal('outwardReferralShowModal', 'outwardReferralShowPanel');
            var referralFlag = document.getElementById('outward_refer_to_higher_facility');
            if (referralFlag) {
                referralFlag.value = '0';
            }
            outwardReferralWizardState.currentStep = 1;
            outwardReferralWizardUpdateUi();
        }

        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Escape') return;
            var modals = window.Alpine.store('modals');
            if (modals.outward) {
                closeConsultationOutwardReferralWizard();
            } else if (modals.consultation) {
                if (consultationCreateModalView === 'wizard') {
                    closeOutwardReferralWizard();
                    return;
                }
                closeConsultationCreateModal();
            } else if (modals.printReferral) {
                closePrintReferralConfirmModal();
            } else if (modals.diagnosis && typeof window.closeDiagnosisModal === 'function') {
                window.closeDiagnosisModal();
            } else if (modals.prescription && typeof window.closePrescriptionModal === 'function') {
                window.closePrescriptionModal();
            }
        });

        function openPrintReferralConfirmModal(referralId) {
            var link = document.getElementById('printReferralConfirmLink');
            if (!link || !referralId) return;

            link.href = '/referrals/' + encodeURIComponent(referralId) + '/print';
            showModal('printReferralConfirmModal', 'printReferralConfirmPanel');
        }

        function closePrintReferralConfirmModal() {
            hideModal('printReferralConfirmModal', 'printReferralConfirmPanel');
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (window.BHCIS.openConsultationFor) {
                openConsultationCreateModal(window.BHCIS.openConsultationFor);
            }
            if (window.BHCIS.printReferralId) {
                openPrintReferralConfirmModal(window.BHCIS.printReferralId);
            }
        });

        // Session timeout check
        setInterval(function() {
            fetch(window.BHCIS.routes.sessionStatus, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (!data.active) {
                    Swal.fire({
                        title: 'Session Expired',
                        text: 'Your session has expired due to inactivity. You will be redirected to the login page.',
                        icon: 'warning',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                    }).then(() => {
                        window.location.href = '/login';
                    });
                }
            })
            .catch(error => {
                console.error('Session check failed:', error);
            });
        }, 30000); // Check every 30 seconds

        if (window.BHCIS.canPollLiveRequests) {
        (function() {
            var liveToast = document.getElementById('liveConsultationToast');
            var liveToastAccept = document.getElementById('liveToastAccept');
            var liveToastDecline = document.getElementById('liveToastDecline');
            var liveToastCloseTimer = null;
            var lastPolledRequestId = null;
            var pollingEnabled = true;

            function playLiveToastChime() {
                if (!window.AudioContext && !window.webkitAudioContext) {
                    return;
                }
                var AudioContext = window.AudioContext || window.webkitAudioContext;
                var ctx = new AudioContext();
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(880, ctx.currentTime);
                gain.gain.setValueAtTime(0, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(0.18, ctx.currentTime + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.7);
            }

            function notifyBrowser(payload) {
                if (!('Notification' in window)) {
                    return;
                }

                if (Notification.permission === 'default') {
                    Notification.requestPermission();
                }

                if (Notification.permission === 'granted') {
                    var notification = new Notification(payload.title, {
                        body: payload.message,
                        tag: 'live-consultation-alert',
                    });
                    notification.onclick = function() {
                        window.focus();
                        if (payload.openUrl) {
                            window.location.href = payload.openUrl;
                        }
                    };
                }
            }

            function dismissConsultationToast() {
                if (!liveToast) {
                    return;
                }
                liveToast.classList.remove('active');
                if (liveToastCloseTimer) {
                    clearTimeout(liveToastCloseTimer);
                    liveToastCloseTimer = null;
                }
            }

            function showConsultationToast(request) {
                if (!liveToast || !request || !request.id) {
                    return;
                }

                // Skip if we already showed this one
                if (lastPolledRequestId === request.id) {
                    return;
                }

                lastPolledRequestId = request.id;

                document.getElementById('liveToastTitle').textContent = 'New Consultation Request';
                document.getElementById('liveToastSubtitle').textContent = request.clinic_name + ' · BHW: ' + request.worker_name;
                document.getElementById('liveToastPatient').textContent = request.patient_name + ' • ' + (request.patient_age ? request.patient_age + ' y/o' : 'Age unknown') + (request.patient_gender ? ' / ' + request.patient_gender : '');
                document.getElementById('liveToastDetails').textContent = 'Reason: ' + (request.chief_complaint || 'No complaint provided');
                document.getElementById('liveToastReason').textContent = request.chief_complaint || 'No complaint provided';

                liveToastAccept.onclick = function() {
                    pollingEnabled = false;  // Stop polling when accepted
                    window.location.href = request.open_url;
                };
                liveToastDecline.onclick = function() {
                    dismissConsultationToast();
                };

                liveToast.classList.add('active');
                liveToast.classList.remove('hidden');
                if (liveToastCloseTimer) {
                    clearTimeout(liveToastCloseTimer);
                }
                liveToastCloseTimer = setTimeout(dismissConsultationToast, 18000);

                playLiveToastChime();
                notifyBrowser({
                    title: 'New Consultation Request',
                    message: request.patient_name + ' • ' + (request.patient_age ? request.patient_age + ' y/o' : 'Age unknown') + '\n' + request.chief_complaint,
                    openUrl: request.open_url,
                });
            }

            /**
             * Polls the server for live consultation requests
             * 
             * Sends a GET request to the '/consultations/live-requests' endpoint
             * to fetch active consultation requests in real-time.
             * 
             * Uses the Fetch API with credentials set to 'same-origin' to ensure
             * cookies and authentication tokens are included in the request.
             * 
             * Sets appropriate headers:
             * - 'X-Requested-With': Identifies the request as XMLHttpRequest (AJAX)
             * - 'Accept': Specifies the response format as JSON
             * 
             * @returns {Promise} A promise that resolves with the server response
             */
            function pollLiveConsultationRequests() {
                if (!pollingEnabled) {
                    return Promise.resolve();
                }

                return fetch(window.BHCIS.routes.liveRequests, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('Live request fetch failed');
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (data.hasRequest && data.request) {
                        showConsultationToast(data.request);
                    }
                })
                .catch(function(error) {
                    console.error('Live consultation poll failed:', error);
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                pollLiveConsultationRequests();
                setInterval(pollLiveConsultationRequests, 12000);
            });
        })();
        }

window.openConsultationCreateModal = openConsultationCreateModal;
window.closeConsultationCreateModal = closeConsultationCreateModal;
window.openOutwardReferralWizard = openOutwardReferralWizard;
window.closeOutwardReferralWizard = closeOutwardReferralWizard;
window.openConsultationOutwardReferralWizard = openConsultationOutwardReferralWizard;
window.closeConsultationOutwardReferralWizard = closeConsultationOutwardReferralWizard;
window.outwardReferralWizardGoNext = outwardReferralWizardGoNext;
window.outwardReferralWizardGoBack = outwardReferralWizardGoBack;
