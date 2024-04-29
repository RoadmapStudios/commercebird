import { useStorage } from "@/composable/storage";
import { backendAction, storeKey } from "@/keys";
import { fetchData } from "./http";
      const storage = useStorage();

    
  /**
    * @param moduleName Module for getting acf fields
    * @description Function to get acf fields
    */ 
  export async function get_acf_fields(postType:string){
    let fields;
    const key =`${postType}_acf_fields`;
    const instore = storage.get(key);            
    if(instore){
        fields= instore.fields;
    }else{
        const actions = backendAction.acf_fields;
        const action =actions.get_acf_fields;
        const response = await fetchData(
            action,
            key,
            {post_type:postType}
        ); 
         fields=response.fields;               
       
        }
        if(fields){
            if(Array.isArray(fields)&&fields.length>0){
                let obj: { [key: string]: string } = {};
                fields.forEach((field:any)=>{
                    obj[field.name]=field.label;
                });
                fields=obj;
                return fields;
            }
            else{
                return {};
            }
    }
    
}