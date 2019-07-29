using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;

namespace QuickDRY
{
    public class BasePage : System.Web.UI.Page
    {
        public BasePage()
        {
            Constants.Init();
        }
    }
}