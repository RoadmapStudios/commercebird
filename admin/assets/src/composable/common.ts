import { useStorage } from "@/composable/storage";
import { backendAction, storeKey } from "@/keys";
import { fetchData } from "./http";
      const storage = useStorage();

    
  /**
    * @param moduleName Module for getting acf fields
    * @description Function to get acf fields
    */ 
  export async function get_acf_fields(moduleName:string){
    const moduleKey =`${moduleName.toLowerCase()}_acf_fields`;
    const actions = backendAction.acf_fields;
    storage.remove(moduleKey);
    const action =actions.get_acf_fields;
   
    const response = await fetchData(
        action,
        moduleKey,
        {module:moduleName}
    );
    if(response){
        if(Array.isArray(response.fields)&&response.fields.length>0){
            let obj: { [key: string]: string } = {};
            response.fields.forEach((field:any)=>{
                obj[field.id]=field.displayLabel;
            });
            response.fields=obj;
            return response.fields;
        }
        else{
            return {};
        }
    }
}