pdfjsLib.GlobalWorkerOptions.workerSrc =
    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

let pdfDoc = null;
let pageActuelle = 1;
let totalPages = 1;
let pdfBytes = null;

document.getElementById('input-pdf').addEventListener('change', async function(e){
    const fichier = e.target.files[0];
    if(!fichier) return;
    const reader = new FileReader();
    reader.onload = async function(evt) {
        pdfBytes = evt.target.result.slice(0);
        const loadingTask = pdfjsLib.getDocument({data: pdfBytes.slice(0)});
        pdfDoc = await loadingTask.promise;
        totalPages = pdfDoc.numPages;
        pageActuelle = 1;
        document.getElementById('section-import').style.display = 'none';
        document.getElementById('section-travail').style.display = 'grid';
        redimensionner();
        afficherPage(pageActuelle);
    };
    reader.readAsArrayBuffer(fichier);
});

async function afficherPage(numPage) {
    const page = await pdfDoc.getPage(numPage);
    const conteneur = document.getElementById('zone-pdf');
    const largeurMax = conteneur.clientWidth - 40;
    const viewport1 = page.getViewport({scale: 1});
    const scale = largeurMax / viewport1.width;
    const viewport = page.getViewport({scale});
    const canvas = document.getElementById('canvas-pdf');
    canvas.width = viewport.width;
    canvas.height = viewport.height;
    await page.render({
        canvasContext: canvas.getContext('2d'),
        viewport: viewport
    }).promise;
    document.getElementById('num-page').textContent = `Page ${pageActuelle} / ${totalPages}`;
}

document.getElementById('btn-precedent').addEventListener('click', () => {
    if (pageActuelle <= 1) return;
    pageActuelle--;
    afficherPage(pageActuelle);
});

document.getElementById('btn-suivant').addEventListener('click', () => {
    if (pageActuelle >= totalPages) return;
    pageActuelle++;
    afficherPage(pageActuelle);
});

const canvasSig = document.getElementById('canvas-signature');
const ctxSig = canvasSig.getContext('2d');
let dessin = false;

function redimensionner(){
    const rect = canvasSig.getBoundingClientRect();
    canvasSig.width = rect.width;
    canvasSig.height = rect.height;
}
window.addEventListener('load', redimensionner);
window.addEventListener('resize', redimensionner);

function getPos(e){
    const rect = canvasSig.getBoundingClientRect();
    if(e.touches){
        return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
    }
    return { x: e.clientX - rect.left, y: e.clientY - rect.top };
}

canvasSig.addEventListener('mousedown', e => {
    dessin = true;
    ctxSig.beginPath();
    ctxSig.moveTo(getPos(e).x, getPos(e).y);
});
canvasSig.addEventListener('mousemove', e => {
    if(!dessin) return;
    const pos = getPos(e);
    ctxSig.lineTo(pos.x, pos.y);
    ctxSig.strokeStyle = '#081935';
    ctxSig.lineWidth = 2;
    ctxSig.lineCap = 'round';
    ctxSig.lineJoin = 'round';
    ctxSig.stroke();
});
canvasSig.addEventListener('mouseup', () => { dessin = false; });
canvasSig.addEventListener('mouseleave', () => { dessin = false; });

document.getElementById('btn-effacer').addEventListener('click', () => {
    ctxSig.clearRect(0, 0, canvasSig.width, canvasSig.height);
});
document.getElementById('btn-valider').addEventListener('click', async () => {
    if (!pdfBytes) {
        document.getElementById('statut').textContent = "Veuillez d'abord importer un PDF.";
        return;
    }
    const imgData = ctxSig.getImageData(0, 0, canvasSig.width, canvasSig.height);
    const vide = imgData.data.every(v => v === 0);
    if (vide) {
        document.getElementById('statut').textContent = 'Veuillez dessiner votre signature.';
        return;
    }

    document.getElementById('statut').textContent = ' Génération du PDF signé...';

    try {
        const signaturePng = canvasSig.toDataURL('image/png');
        const pdfLibDoc = await PDFLib.PDFDocument.load(pdfBytes);
        const signatureImage = await pdfLibDoc.embedPng(signaturePng);
        const pages = pdfLibDoc.getPages();
        const dernierePage = pages[pages.length - 1];
        const { width, height } = dernierePage.getSize();

        dernierePage.drawImage(signatureImage, {
            x: width - 220, y: 40, width: 180, height: 60,
        });

        const font = await pdfLibDoc.embedFont(PDFLib.StandardFonts.Helvetica);
        const date = new Date().toLocaleDateString('fr-FR');
        dernierePage.drawText(`Signé électroniquement le ${date}`, {
            x: width - 220, y: 26, size: 8, font: font,
            color: PDFLib.rgb(0.4, 0.4, 0.4),
        });

        const pdfFinal = await pdfLibDoc.save();

        const blob = new Blob([pdfFinal], { type: 'application/pdf' });
        const formData = new FormData();
        formData.append('contrat', blob, 'contrat_signe.pdf');

        document.getElementById('statut').textContent = ' Envoi en cours...';

        const reponse = await fetch('save_contrat.php', {
            method: 'POST',
            body: formData
        });

        const resultat = await reponse.json();

        if (resultat.success) {
            
            const urlPdf = URL.createObjectURL(blob);
            const lien = document.createElement('a');
            lien.href = urlPdf;
            lien.download = 'contrat_signe_' + new Date().toISOString().slice(0, 10) + '.pdf';
            document.body.appendChild(lien);
            lien.click();
            document.body.removeChild(lien);
            URL.revokeObjectURL(urlPdf);
            
            document.getElementById('statut').textContent = 'Contrat signé et téléchargé !';
        } else {
            document.getElementById('statut').textContent = 'Erreur : ' + resultat.error;
        }
    } catch (err) {
        console.error(err);
        document.getElementById('statut').textContent = 'Erreur lors de la génération du PDF signé.';
    }
});