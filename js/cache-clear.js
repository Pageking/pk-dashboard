// document.addEventListener('DOMContentLoaded', function() {
//     var clearCacheLink = document.getElementById('clear-cache');
//     if (clearCacheLink) {
//         clearCacheLink.addEventListener('click', function(e) {
//             e.preventDefault();
//             
//             // Check if pkCacheClear is defined
//             if (typeof pkCacheClear === 'undefined') {
//                 console.error('pkCacheClear is not defined. WordPress may not have localized the script correctly.');
//                 alert('An error occurred. Please check the console and contact the administrator.');
//                 return;
//             }
// 
//             var xhr = new XMLHttpRequest();
//             xhr.open('POST', pkCacheClear.ajax_url, true);
//             xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
//             xhr.onload = function() {
//                 if (xhr.status === 200) {
//                     var response = JSON.parse(xhr.responseText);
//                     if (response.success) {
//                         alert('Cache cleared successfully!');
//                     } else {
//                         alert('Failed to clear cache: ' + response.data);
//                     }
//                 } else {
//                     alert('Failed to clear cache. Please try again.');
//                 }
//             };
//             xhr.send('action=pk_clear_cache&nonce=' + pkCacheClear.nonce);
//         });
//     } else {
//         console.error('Clear cache link not found');
//     }
// });


// document.addEventListener('DOMContentLoaded', function() {
//   const button = document.getElementById('pk-cache-button');
//   
//   button.addEventListener('click', function(e) {
//       e.preventDefault();
//       this.disabled = true;
//       this.value = 'Caching wordt geleegd...';
//       
//       const formData = new FormData();
//       formData.append('clear_cache', '1');
//       
//       fetch(window.location.href, {
//           method: 'POST',
//           body: formData
//       }).then(() => {
//           this.value = 'Cache geleegd!';
//           setTimeout(() => {
//               this.disabled = false;
//               this.value = 'Leeg caching';
//           }, 12000);
//       });
//   });
// });