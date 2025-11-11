import { Controller } from "@hotwired/stimulus";

export default class FileUploadController extends Controller {
    static targets = ["input", "preview", "dropzone", "label", "filename", "previewContainer", "removeFlag", "currentImageContainer", "dropzoneContainer", "currentImage"];
    static values = {
        accept: String,
        maxSize: Number,
        currentImage: String,
    };

    connect() {
        this.setupDropzone();
        this.setupInput();
        // Si une image actuelle existe, afficher l'image actuelle et cacher le dropzone et le preview
        if (this.currentImageValue) {
            // Cacher le preview et le conteneur du dropzone car l'image actuelle est affichée
            if (this.hasPreviewContainerTarget) {
                this.previewContainerTarget.classList.add("hidden");
            }
            if (this.hasDropzoneContainerTarget) {
                this.dropzoneContainerTarget.classList.add("hidden");
            }
            // Afficher l'image actuelle
            if (this.hasCurrentImageContainerTarget) {
                this.currentImageContainerTarget.classList.remove("hidden");
            }
        } else {
            // Pas d'image actuelle, cacher le preview et l'image actuelle, afficher le dropzone
            if (this.hasPreviewContainerTarget) {
                this.previewContainerTarget.classList.add("hidden");
            }
            if (this.hasCurrentImageContainerTarget) {
                this.currentImageContainerTarget.classList.add("hidden");
            }
            if (this.hasDropzoneContainerTarget) {
                this.dropzoneContainerTarget.classList.remove("hidden");
            }
        }
    }

    setupDropzone() {
        if (!this.hasDropzoneTarget) return;

        this.dropzoneTarget.addEventListener("dragover", this.handleDragOver.bind(this));
        this.dropzoneTarget.addEventListener("dragleave", this.handleDragLeave.bind(this));
        this.dropzoneTarget.addEventListener("drop", this.handleDrop.bind(this));
        this.dropzoneTarget.addEventListener("click", () => {
            if (this.hasInputTarget) {
                this.inputTarget.click();
            }
        });
    }

    setupInput() {
        if (!this.hasInputTarget) return;

        this.inputTarget.addEventListener("change", this.handleFileSelect.bind(this));
    }

    handleDragOver(event) {
        event.preventDefault();
        event.stopPropagation();
        this.dropzoneTarget.classList.add("border-primary", "bg-primary/5");
    }

    handleDragLeave(event) {
        event.preventDefault();
        event.stopPropagation();
        this.dropzoneTarget.classList.remove("border-primary", "bg-primary/5");
    }

    handleDrop(event) {
        event.preventDefault();
        event.stopPropagation();
        this.dropzoneTarget.classList.remove("border-primary", "bg-primary/5");

        const files = event.dataTransfer.files;
        if (files.length > 0) {
            this.processFile(files[0]);
        }
    }

    handleFileSelect(event) {
        const files = event.target.files;
        if (files.length > 0) {
            this.processFile(files[0]);
        }
    }

    processFile(file) {
        // Validate file type
        if (this.acceptValue && !this.isValidFileType(file)) {
            alert(`Invalid file type. Allowed types: ${this.acceptValue}`);
            return;
        }

        // Validate file size
        if (this.maxSizeValue && file.size > this.maxSizeValue * 1024 * 1024) {
            alert(`File size exceeds maximum of ${this.maxSizeValue}MB`);
            return;
        }

        // Update input
        if (this.hasInputTarget) {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            this.inputTarget.files = dataTransfer.files;
            // Ne pas déclencher l'événement change avec bubbles pour éviter la soumission automatique du formulaire
            // L'événement change est déclenché sans propagation pour éviter la soumission automatique
            const changeEvent = new Event("change", { bubbles: false });
            this.inputTarget.dispatchEvent(changeEvent);
        }

        // Update preview
        this.updatePreview(file);

        // Update filename
        if (this.hasFilenameTarget) {
            this.filenameTarget.textContent = file.name;
        }
    }

    isValidFileType(file) {
        if (!this.acceptValue) return true;

        const acceptedTypes = this.acceptValue.split(",").map(type => type.trim());
        const fileExtension = "." + file.name.split(".").pop().toLowerCase();
        const fileType = file.type;

        return acceptedTypes.some(accepted => {
            if (accepted.startsWith(".")) {
                return accepted.toLowerCase() === fileExtension;
            }
            if (accepted.includes("/")) {
                return fileType === accepted || fileType.startsWith(accepted.split("/")[0] + "/");
            }
            return false;
        });
    }

    updatePreview(file = null) {
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                // Toujours utiliser le currentImageContainer pour afficher la nouvelle image
                if (this.hasCurrentImageContainerTarget) {
                    // Mettre à jour l'image dans le conteneur
                    if (this.hasCurrentImageTarget) {
                        this.currentImageTarget.src = e.target.result;
                    } else {
                        // Fallback: chercher l'image dans le conteneur
                        const currentImage = this.currentImageContainerTarget.querySelector('img[data-image-resize-target="image"]');
                        if (currentImage) {
                            currentImage.src = e.target.result;
                        }
                    }
                    // Afficher le conteneur de l'image
                    this.currentImageContainerTarget.classList.remove("hidden");
                    // Cacher le conteneur du dropzone
                    if (this.hasDropzoneContainerTarget) {
                        this.dropzoneContainerTarget.classList.add("hidden");
                    }
                    // Cacher le preview si visible
                    if (this.hasPreviewContainerTarget) {
                        this.previewContainerTarget.classList.add("hidden");
                    }
                } else {
                    // Fallback: afficher le preview si le conteneur n'existe pas
                    if (this.hasPreviewTarget) {
                        this.previewTarget.src = e.target.result;
                    }
                    if (this.hasPreviewContainerTarget) {
                        this.previewContainerTarget.classList.remove("hidden");
                    } else if (this.hasPreviewTarget && this.previewTarget.parentElement) {
                        this.previewTarget.parentElement.classList.remove("hidden");
                    }
                    if (this.hasDropzoneContainerTarget) {
                        this.dropzoneContainerTarget.classList.add("hidden");
                    }
                }
                // Réinitialiser le flag de suppression car on a une nouvelle image
                if (this.hasRemoveFlagTarget) {
                    this.removeFlagTarget.value = "0";
                }
            };
            reader.readAsDataURL(file);
        } else if (this.currentImageValue) {
            // Afficher l'image actuelle - cacher le preview et le conteneur du dropzone
            if (this.hasPreviewContainerTarget) {
                this.previewContainerTarget.classList.add("hidden");
            }
            if (this.hasCurrentImageContainerTarget) {
                this.currentImageContainerTarget.classList.remove("hidden");
            }
            if (this.hasDropzoneContainerTarget) {
                this.dropzoneContainerTarget.classList.add("hidden");
            }
        } else {
            // Pas d'image - afficher le conteneur du dropzone, cacher le preview et l'image actuelle
            if (this.hasPreviewContainerTarget) {
                this.previewContainerTarget.classList.add("hidden");
            }
            if (this.hasCurrentImageContainerTarget) {
                this.currentImageContainerTarget.classList.add("hidden");
            }
            if (this.hasDropzoneContainerTarget) {
                this.dropzoneContainerTarget.classList.remove("hidden");
            }
        }
    }

    removeFile() {
        if (this.hasInputTarget) {
            this.inputTarget.value = "";
            // Ne pas déclencher l'événement change avec bubbles pour éviter la soumission automatique du formulaire
            const changeEvent = new Event("change", { bubbles: false });
            this.inputTarget.dispatchEvent(changeEvent);
        }

        if (this.hasPreviewTarget) {
            this.previewTarget.src = "";
        }

        // Cacher le conteneur de preview
        if (this.hasPreviewContainerTarget) {
            this.previewContainerTarget.classList.add("hidden");
        } else if (this.hasPreviewTarget && this.previewTarget.parentElement) {
            this.previewTarget.parentElement.classList.add("hidden");
        }

        // Cacher le conteneur de l'image actuelle
        if (this.hasCurrentImageContainerTarget) {
            this.currentImageContainerTarget.classList.add("hidden");
        }

        if (this.hasDropzoneContainerTarget) {
            this.dropzoneContainerTarget.classList.remove("hidden");
        }

        if (this.hasFilenameTarget) {
            this.filenameTarget.textContent = "";
        }

        // Réinitialiser le flag de suppression
        if (this.hasRemoveFlagTarget) {
            this.removeFlagTarget.value = "0";
        }
        
        // Réinitialiser l'aperçu et réafficher l'image actuelle si elle existe
        this.updatePreview();
    }

    removeCurrentImage() {
        // Marquer l'image actuelle pour suppression
        if (this.hasRemoveFlagTarget) {
            this.removeFlagTarget.value = "1";
            // S'assurer que le champ est dans le formulaire
            const form = this.removeFlagTarget.closest('form');
            if (form) {
                // Déclencher un événement pour notifier le changement
                this.removeFlagTarget.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // Cacher le conteneur de l'image actuelle
        if (this.hasCurrentImageContainerTarget) {
            this.currentImageContainerTarget.classList.add("hidden");
        }

        // Afficher le dropzone
        if (this.hasDropzoneContainerTarget) {
            this.dropzoneContainerTarget.classList.remove("hidden");
        }

        // Cacher le preview si visible
        if (this.hasPreviewContainerTarget) {
            this.previewContainerTarget.classList.add("hidden");
        }

        // Réinitialiser l'image actuelle
        this.currentImageValue = "";
        
        // Réinitialiser l'input file
        if (this.hasInputTarget) {
            this.inputTarget.value = "";
        }
        
        // Réinitialiser l'aperçu
        this.updatePreview();
    }
}

