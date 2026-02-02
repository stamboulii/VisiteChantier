// Registrer le Service Worker pour la PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('../sw.js')
            .then(reg => console.log('Service Worker enregistré !', reg))
            .catch(err => console.log('Erreur SW:', err));
    });
}

// Fonction pour ouvrir l'image en modal
function openImageModal(imageSrc) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    
    if (modal && modalImage) {
        modalImage.src = imageSrc;
        modal.style.display = 'flex';
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Animation d'entrée
        setTimeout(() => {
            modalImage.style.opacity = '1';
            modalImage.style.transform = 'scale(1)';
        }, 10);
    }
}

// Fonction pour fermer le modal
function closeImageModal() {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    
    if (modal && modalImage) {
        // Animation de sortie
        modalImage.style.opacity = '0';
        modalImage.style.transform = 'scale(0.8)';
        
        setTimeout(() => {
            modal.style.display = 'none';
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }, 300);
    }
}

// Fermer le modal avec la touche Échap
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
});

// Prévisualisation de l'image avant upload
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('image');
    
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Vérifier la taille
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    showNotification('Le fichier est trop volumineux. Taille maximale: 5MB', 'error');
                    fileInput.value = '';
                    return;
                }
                
                // Vérifier le type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showNotification('Format de fichier non autorisé. Formats acceptés: JPG, PNG, GIF', 'error');
                    fileInput.value = '';
                    return;
                }
                
                // Afficher le nom du fichier avec animation
                const label = document.querySelector('.file-input-label');
                if (label) {
                    label.innerHTML = `✓ ${file.name}`;
                    label.style.background = 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)';
                    label.style.color = 'white';
                    label.style.borderColor = 'transparent';
                }
            }
        });
    }
    
    // Animation des cartes au scroll
    observeElements();
    
    // Animation des statistiques
    animateStats();
});

// Observer pour animations au scroll
function observeElements() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, {
        threshold: 0.1
    });
    
    // Observer les cartes de chantiers
    document.querySelectorAll('.chantier-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
    
    // Observer les items de galerie
    document.querySelectorAll('.gallery-item').forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        item.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
        observer.observe(item);
    });
}

// Animation des statistiques
function animateStats() {
    const statCards = document.querySelectorAll('.stat-card h3');
    
    statCards.forEach(stat => {
        const finalValue = parseInt(stat.textContent);
        let currentValue = 0;
        const increment = Math.ceil(finalValue / 50);
        const duration = 1000; // 1 seconde
        const stepTime = duration / (finalValue / increment);
        
        const counter = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                stat.textContent = finalValue;
                clearInterval(counter);
            } else {
                stat.textContent = currentValue;
            }
        }, stepTime);
    });
}

// Système de notifications
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '10000';
    notification.style.minWidth = '300px';
    notification.style.animation = 'slideInRight 0.5s ease';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.5s ease';
        setTimeout(() => notification.remove(), 500);
    }, 4000);
}

// Confirmation avant suppression (pour future fonctionnalité)
function confirmDelete(message) {
    return confirm(message || 'Êtes-vous sûr de vouloir supprimer cet élément ?');
}

// Animation de chargement pour les formulaires
document.querySelectorAll('form').forEach(form => {
    const originalText = {};
    
    form.addEventListener('submit', function(e) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn && !submitBtn.disabled) {
            originalText[form.id || 'default'] = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading"></span> Chargement...';
            
            // Réactiver après 10 secondes au cas où
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText[form.id || 'default'];
            }, 10000);
        }
    });
});

// Masquer les messages d'alerte après quelques secondes
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

// Ajouter animations CSS manquantes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// ===============================================
// Fonctions pour la gestion des images
// ===============================================

// Ouvrir la modal d'édition d'une image
function editImage(imageId, phase, commentaire, datePrise) {
    const modal = document.getElementById('editImageModal');

    // Remplir les champs du formulaire
    document.getElementById('edit_image_id').value = imageId;
    document.getElementById('edit_phase').value = phase;
    document.getElementById('edit_commentaire').value = commentaire;
    document.getElementById('edit_date_prise').value = datePrise;

    // Afficher la modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Fermer la modal d'édition
function closeEditModal() {
    const modal = document.getElementById('editImageModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Gérer la soumission du formulaire d'édition
document.addEventListener('DOMContentLoaded', function() {
    const editForm = document.getElementById('editImageForm');

    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(editForm);

            fetch('../api/edit-image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeEditModal();

                    // Recharger la page pour afficher les modifications
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur lors de la mise à jour de l\'image', 'error');
            });
        });
    }

    // Fermer la modal avec Échap
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeEditModal();
        }
    });

    // Fermer la modal en cliquant sur le fond
    const editModal = document.getElementById('editImageModal');
    if (editModal) {
        editModal.addEventListener('click', function(e) {
            if (e.target === editModal) {
                closeEditModal();
            }
        });
    }
});

// Supprimer une image
function deleteImage(imageId) {
    if (!confirmDelete('Êtes-vous sûr de vouloir supprimer cette image ? Cette action est irréversible.')) {
        return;
    }

    const formData = new FormData();
    formData.append('image_id', imageId);

    fetch('../api/delete-image.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');

            // Supprimer visuellement l'image avec animation
            const galleryItem = document.querySelector(`.gallery-item[data-image-id="${imageId}"]`);
            if (galleryItem) {
                galleryItem.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                galleryItem.style.opacity = '0';
                galleryItem.style.transform = 'scale(0.8)';

                setTimeout(() => {
                    galleryItem.remove();

                    // Mettre à jour le compteur de photos
                    const countElement = document.querySelector('.gallery-count');
                    if (countElement) {
                        const currentCount = parseInt(countElement.textContent);
                        countElement.textContent = `${currentCount - 1} photo(s)`;
                    }

                    // Afficher le message vide si plus aucune photo
                    const gallery = document.querySelector('.gallery');
                    if (gallery && gallery.children.length === 0) {
                        gallery.innerHTML = '<div class="empty-state"><p>Aucune photo pour ce projet. Uploadez votre première photo ci-dessus !</p></div>';
                    }
                }, 500);
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la suppression de l\'image', 'error');
    });
}
