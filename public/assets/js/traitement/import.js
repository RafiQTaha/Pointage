$(document).ready(function () {
    console.clear();
    const Importation = async (formData,icon) => {
        try {
          const request = await axios.post('/auto/import/importation',formData);
          const response = request.data;
          console.log(response);
          table.ajax.reload();
          Toast.fire({
            icon: 'success',
            title: response,
          })
        } catch (error) {
          console.log(error.response);
          const message = error.response;
          Toast.fire({
              icon: 'error',
              title: message,
          })
        }
        icon.addClass('fa-check').removeClass("fa-spinner fa-spin");
      }
    
      
      const ImportationSyncCR = async () => {
        console.log('ImportationSync ');
        var date = new Date();
        let formData = new FormData();
        formData.append('date',"")
        try {
            const request = await axios.post('/auto/import/importation/cr',formData);
            let response = request.data
            console.log(date)
            ImportationSync()
            console.log(response);
        } catch (error) {
            const message = error.response.data;
            console.log('Error Importation ------')
            console.log(message)
        }
      }
      const ImportationSyncSTG = async () => {
        console.log('ImportationSync ');
        var date = new Date();
        let formData = new FormData();
        formData.append('date',"")
        try {
            const request = await axios.post('/auto/import/importation/stg',formData);
            let response = request.data
            console.log(date)
            ImportationSync()
            console.log(response);
        } catch (error) {
            const message = error.response.data;
            console.log('Error Importation ------')
            console.log(message)
        }
      }
      ImportationSyncCR()
      ImportationSyncSTG()
});
