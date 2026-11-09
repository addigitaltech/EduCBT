import '@fontsource-variable/inter';
import Alpine from 'alpinejs';
import { createIcons, icons } from 'lucide';

window.Alpine = Alpine;
Alpine.start();

// One icon library (Lucide). Every icon in the interface sits next to a visible
// text label, so a missing icon never hides meaning.
// Phase 9 will trim this to only the icons in use to reduce the download size.
createIcons({ icons });
