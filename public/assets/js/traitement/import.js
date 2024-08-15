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
        console.log('ImportationSync Cours ');
        var date = new Date();
        let formData = new FormData();
        formData.append('date',"")
        try {
            const request = await axios.post('/auto/import/importation/cr',formData);
            let response = request.data
            console.log(date)
            ImportationSyncCR()
            console.log(response);
        } catch (error) {
            const message = error.response.data;
            console.log('Error Importation ------')
            console.log(message)
        }
      }
      const ImportationSyncSTG = async () => {
        console.log('ImportationSync Stage ');
        var date = new Date();
        let formData = new FormData();
        formData.append('date',"")
        try {
            const request = await axios.post('/auto/import/importation/stg',formData);
            let response = request.data
            console.log(date)
            ImportationSyncSTG()
            console.log(response);
        } catch (error) {
            const message = error.response.data;
            console.log('Error Importation ------')
            console.log(message)
        }
      }

      const ImportationMinuit = async () => {
        console.log('ImportationSync Minuit ');
        var date = new Date();
        var dateBefore = new Date(date);
        dateBefore.setDate(dateBefore.getDate() - 1);
        var formattedDate = dateBefore.toISOString().split('T')[0];
        // console.log(dateBefore);
        // return
        if(date.getHours() >= 0 && date.getHours() < 1 ){
          let formData = new FormData();
          formData.append('date',formattedDate)
          try {
              const request = await axios.post('/auto/import/importation/minuit',formData);
              let response = request.data
              console.log(date)
              console.log(response);
          } catch (error) {
              const message = error.response.data;
              console.log('Error Importation ------')
              console.log(message)
          }
        }
      }
      
      const ImportationMinuitResidanat = async () => {
        console.log('ImportationSync Residanat');
        var date = new Date();
        try {
        const request = await axios.post('/auto/import/importation/minuitResidanat');
        let response = request.data
        console.log(date)
        console.log(response);
        // SynchronisationReglements();
        } catch (error) {
            const message = error.response.data;
            console.log('Error Importation ------')
            console.log(message)
        }
    }

      ImportationMinuit()
      setInterval(ImportationMinuit, 3600000);
      
      ImportationSyncCR()
      ImportationSyncSTG()


    window.setInterval(function(){ 
        var date = new Date(); 
    
        if(date.getHours() === 2 && date.getMinutes()  <= 5){ // Check the time
          ImportationMinuitResidanat() 
        }       
    }, 300000);
});
